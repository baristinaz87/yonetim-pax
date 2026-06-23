<?php

declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Models\Shopify\Event;
use App\Models\Shopify\StoreApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Shopify "app uninstalled" event'i için arka plan işi.
 *
 * Akış:
 *   1. App'in webhook_url'sine POST at (fire-and-forget)
 *   2. StoreApp.status = 'uninstalled', uninstalled_at set
 *   3. Access token bellekten silinsin diye null yapılır
 *
 * Bu job InstallJob'un simetriği — gelecekte buraya da dış entegrasyonlar
 * (clean-up, audit, vs.) eklenebilir.
 */
class UninstallJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(public int $eventId) {}

    public function handle(): void
    {
        /** @var Event|null $event */
        $event = Event::with(['store', 'app'])->find($this->eventId);

        if (! $event || $event->type !== 'uninstalled' || ! $event->store || ! $event->app) {
            return;
        }

        $domain = $event->store->domain;
        $app    = $event->app;

        Log::info("[uninstall-job] başladı: app={$app->handle}, store={$domain}, event_id={$event->id}");

        // 1) Dış sisteme uninstall bildirimi
        $this->notifyExternalSystem($app, $event);

        // 2) StoreApp durumunu güncelle, token'ı temizle
        $updated = StoreApp::query()
            ->where('store_id', $event->store->id)
            ->where('app_id', $app->id)
            ->update([
                'status'         => 'uninstalled',
                'uninstalled_at' => $event->created_at ?? now(),
                'access_token'   => null, // uninstall sonrası token geçersiz
            ]);

        Log::info("[uninstall-job] tamamlandı: store={$domain}, app={$app->handle}, affected_rows={$updated}");
    }

    private function notifyExternalSystem(\App\Models\Shopify\App $app, Event $event): void
    {
        if (! $app->webhook_url) {
            return;
        }

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 10,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
            ]);

            $client->post($app->webhook_url, [
                'json' => [
                    'event'       => 'app_uninstalled',
                    'app_handle'  => $app->handle,
                    'app_name'    => $app->name,
                    'shop'        => $event->store?->domain,
                    'occurred_at' => $event->created_at?->toIso8601String(),
                    'event_id'    => $event->id,
                ],
            ]);

            Log::info("[uninstall-job] {$app->handle} webhook_url POST: {$app->webhook_url}");
        } catch (\Throwable $e) {
            Log::warning("[uninstall-job] {$app->handle} webhook_url başarısız: ".$e->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[uninstall-job] tüm denemeler başarısız: event_id={$this->eventId}, hata: ".$exception->getMessage());
    }
}