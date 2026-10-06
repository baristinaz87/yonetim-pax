<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Models\Shopify\App;
use App\Services\Shopify\StoreAppEventRecorder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Uygulamaların (efatura vb.) kurulum / kaldırma anında gönderdiği bildirimlerin alıcısı.
 * Partner API olayları geç verebildiği için mağaza, kurulumdan saniyeler sonra
 * buradan öğrenilir; Partner sync aynı olayı sonra getirince yeni event açılmaz.
 *
 * POST /webhooks/shopify/{app:handle}
 * Headers:
 *   X-Shopify-Topic: app/installed | app/uninstalled
 *   X-Shopify-Hmac-Sha256: base64(hmac_sha256(ham gövde, app.client_secret))
 * Body: { shop_domain, occurred_at?, access_token?, shop? }
 *   occurred_at  → olay anı (ISO 8601); yoksa istek anı
 *   access_token → mağazanın Shopify access token'ı (yalnızca app/installed)
 *   shop         → Admin API shop.json içeriği (yalnızca app/installed)
 *
 * Ağır işler (token/app data/shop.json çekimi, flow'lar) yeni event'in
 * observer'ı üzerinden kuyruktaki InstallJob / UninstallJob'da yapılır.
 */
class WebhookController extends Controller
{
    /** Gönderen sunucunun saati ileride olabilir; bundan ilerideki olay zamanı kabul edilmez. */
    private const MAX_CLOCK_SKEW_MINUTES = 5;

    public function __construct(private readonly StoreAppEventRecorder $recorder) {}

    public function __invoke(Request $request, App $app): JsonResponse
    {
        $topic = (string) $request->header('X-Shopify-Topic');

        if ($topic === '') {
            return response()->json(['error' => 'Missing X-Shopify-Topic header'], 400);
        }

        $domain = strtolower(trim((string) $request->input('shop_domain', $request->header('X-Shopify-Shop-Domain'))));

        if (! preg_match('/^[a-z0-9][a-z0-9-]*\.myshopify\.com$/', $domain)) {
            return response()->json(['error' => 'Invalid shop_domain'], 422);
        }

        $occurredAt = $this->parseOccurredAt($request->input('occurred_at'));

        try {
            match ($topic) {
                'app/installed'   => $this->handleInstalled($app, $domain, $occurredAt, $request),
                'app/uninstalled' => $this->handleUninstalled($app, $domain, $occurredAt),
                default           => Log::info("[webhook] unhandled topic: {$topic}"),
            };
        } catch (\Throwable $e) {
            Log::error("[webhook] error ({$topic}, {$domain}): ".$e->getMessage());
            return response()->json(['error' => 'Internal error'], 500);
        }

        return response()->json(['ok' => true]);
    }

    private function handleInstalled(App $app, string $domain, Carbon $occurredAt, Request $request): void
    {
        $shop  = $request->input('shop');
        $shop  = is_array($shop) ? $shop : [];
        $token = $request->input('access_token');

        $event = $this->recorder->recordInstall($app, $domain, $occurredAt, StoreAppEventRecorder::SOURCE_WEBHOOK, [
            'shop_name'    => $shop['name'] ?? null,
            'shop'         => $shop,
            'access_token' => is_string($token) && $token !== '' ? $token : null,
        ]);

        Log::info("[webhook] installed: {$domain} → {$app->handle} "
            .($event ? "(yeni event_id={$event->id})" : '(mevcut kurulum güncellendi)'));
    }

    private function handleUninstalled(App $app, string $domain, Carbon $occurredAt): void
    {
        $event = $this->recorder->recordUninstall($app, $domain, $occurredAt, StoreAppEventRecorder::SOURCE_WEBHOOK);

        Log::info("[webhook] uninstalled: {$domain} → {$app->handle} "
            .($event ? "(yeni event_id={$event->id})" : '(zaten kayıtlı)'));
    }

    private function parseOccurredAt(mixed $value): Carbon
    {
        $now = Carbon::now();

        if (! is_string($value) || $value === '') {
            return $now;
        }

        try {
            $at = Carbon::parse($value);
        } catch (\Throwable) {
            return $now;
        }

        return $at->gt($now->copy()->addMinutes(self::MAX_CLOCK_SKEW_MINUTES)) ? $now : $at;
    }
}
