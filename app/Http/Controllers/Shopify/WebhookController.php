<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use App\Models\Shopify\App;
use App\Models\Shopify\Event;
use App\Models\Shopify\Store;
use App\Models\Shopify\StoreApp;
use App\Services\Shopify\AdminClient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Shopify webhook alıcısı.
 *
 * POST /webhooks/shopify/{app:handle}
 * Headers:
 *   X-Shopify-Topic: app/installed | app/uninstalled
 *   X-Shopify-Hmac-Sha256: <imza>  (opsiyonel, üretimde zorunlu)
 * Body: { shop_domain, access_token, shop }
 */
class WebhookController extends Controller
{
    public function __construct(private readonly AdminClient $admin) {}

    public function __invoke(Request $request, App $app): JsonResponse
    {
        $topic = $request->header('X-Shopify-Topic');

        if (! $topic) {
            return response()->json(['error' => 'Missing X-Shopify-Topic header'], 400);
        }

        $payload       = $request->all();
        $shopDomain    = (string) ($payload['shop_domain'] ?? '');
        $accessToken   = $payload['access_token'] ?? null;
        $shopMeta      = $payload['shop'] ?? [];

        try {
            match ($topic) {
                'app/installed'   => $this->handleInstalled($app, $shopDomain, $accessToken, $shopMeta),
                'app/uninstalled' => $this->handleUninstalled($app, $shopDomain),
                default           => Log::info("[webhook] unhandled topic: {$topic}"),
            };
        } catch (\Throwable $e) {
            Log::error("[webhook] error ({$topic}): ".$e->getMessage());
            return response()->json(['error' => 'Internal error'], 500);
        }

        return response()->json(['ok' => true]);
    }

    private function handleInstalled(App $app, string $domain, ?string $token, array $shopMeta): void
    {
        $store = Store::firstOrCreate(
            ['domain' => $domain],
            ['name'   => $shopMeta['name'] ?? null],
        );

        StoreApp::updateOrCreate(
            [
                'store_id' => $store->id,
                'app_id'   => $app->id,
            ],
            [
                'status'        => 'active',
                'installed_at'  => Carbon::now(),
                'uninstalled_at'=> null,
                'access_token'  => $token,
            ],
        );

        Event::create([
            'store_id'   => $store->id,
            'app_id'     => $app->id,
            'type'       => 'installed',
            'label'      => "Uygulama kuruldu ({$app->name})",
            'created_at' => Carbon::now(),
        ]);

        // Anında Admin API'den mağaza detaylarını çek.
        if ($token) {
            try {
                $this->admin->fetchAndUpdateStoreDetails($domain, $token);
            } catch (\Throwable $e) {
                Log::warning("[webhook] {$domain} admin fetch atlandı: ".$e->getMessage());
            }
        }

        Log::info("[webhook] installed: {$domain} → {$app->name}");
    }

    private function handleUninstalled(App $app, string $domain): void
    {
        $store = Store::where('domain', $domain)->first();
        if (! $store) {
            Log::warning("[webhook] uninstalled: {$domain} store bulunamadı");
            return;
        }

        StoreApp::query()
            ->where('store_id', $store->id)
            ->where('app_id', $app->id)
            ->where('status', 'active')
            ->update([
                'status'        => 'uninstalled',
                'uninstalled_at'=> Carbon::now(),
            ]);

        Event::create([
            'store_id'   => $store->id,
            'app_id'     => $app->id,
            'type'       => 'uninstalled',
            'label'      => "Uygulama kaldırıldı ({$app->name})",
            'created_at' => Carbon::now(),
        ]);

        Log::info("[webhook] uninstalled: {$domain} → {$app->name}");
    }
}
