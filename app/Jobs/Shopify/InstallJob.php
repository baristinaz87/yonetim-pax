<?php

declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Models\Shopify\App;
use App\Models\Shopify\Event;
use App\Models\Shopify\StoreApp;
use App\Services\DeliveryApiClient;
use App\Services\Shopify\AdminClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Shopify "app installed" event'i için arka plan işi.
 *
 * Akış:
 *   1. App.webhook_url'sine POST at (opsiyonel — dış sisteme bildirim)
 *   2. App'in kendi delivery_api konfigürasyonu ile login ol
 *   3. get_access_token_endpoint?shop=... üzerinden Shopify access token çek
 *   4. StoreApp tablosuna token'ı yaz
 *   5. Admin API shop.json çağrısı ile Store satırını güncelle
 *
 * Yeniden deneme:
 *   - 3 deneme, exponential backoff (10s, 30s, 60s)
 *   - failed_jobs tablosuna düşer
 */
class InstallJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(public int $eventId) {}

    public function handle(AdminClient $admin): void
    {
        /** @var Event|null $event */
        $event = Event::with(['store', 'app'])->find($this->eventId);

        if (! $event) {
            Log::warning("[install-job] event bulunamadı: id={$this->eventId}");
            return;
        }

        if ($event->type !== 'installed') {
            Log::warning("[install-job] event 'installed' değil, atlandı: id={$event->id}, type={$event->type}");
            return;
        }

        if (! $event->store || ! $event->app) {
            Log::warning("[install-job] event ilişkileri eksik: id={$event->id}");
            return;
        }

        $domain = $event->store->domain;
        $app    = $event->app;

        Log::info("[install-job] başladı: app={$app->handle}, store={$domain}, event_id={$event->id}");

        // 1) Dış sisteme POST bildirimi (opsiyonel)
        $this->notifyExternalSystem($app, $event);

        // 2) Delivery API ile access token çek
        try {
            $delivery = new DeliveryApiClient($app);
        } catch (\Throwable $e) {
            // delivery konfigürasyonu eksik → sadece logla, job'u fail etme.
            // Çünkü bu job'un birincil görevi delivery değil, webhook + DB güncellemesi.
            Log::warning("[install-job] {$app->handle}: delivery client oluşturulamadı — ".$e->getMessage());
            return;
        }

        try {
            $accessToken = $delivery->getAccessTokenByShop($domain);
        } catch (\Throwable $e) {
            // Delivery API tamamen kapalı — job'u yeniden denensin diye exception fırlat
            Log::error("[install-job] {$app->handle} delivery API hatası: ".$e->getMessage());
            throw $e;
        }

        if (! $accessToken) {
            // Bu app bu mağaza için delivery'de tanımlı değil — normal bir durum
            Log::info("[install-job] {$app->handle}: {$domain} için access token alınamadı, atlandı");
            return;
        }

        // 3) StoreApp tablosuna token'ı yaz
        $storeApp = StoreApp::updateOrCreate(
            [
                'store_id' => $event->store->id,
                'app_id'   => $app->id,
            ],
            [
                'status'         => 'active',
                'access_token'   => $accessToken,
                'installed_at'   => $event->created_at ?? now(),
                'uninstalled_at' => null,
            ],
        );

        Log::info("[install-job] {$domain} → {$app->handle}: access token yazıldı");

        // 4) Admin API ile shop bilgilerini çek ve Store'u güncelle
        try {
            $admin->fetchAndUpdateStoreDetails($domain, $accessToken);
        } catch (\Throwable $e) {
            // Admin API hatası kritik değil — token elimizde, ileride tekrar deneyebiliriz
            Log::warning("[install-job] {$domain} admin shop.json başarısız: ".$e->getMessage());
        }

        Log::info("[install-job] tamamlandı: event_id={$event->id}");
    }

    /**
     * App'in webhook_url adresine install olayını POST'la.
     *
     * Beklenen payload:
     *   {
     *     "event": "app_installed",
     *     "app_handle": "yurtici-kargo",
     *     "app_name": "Yurtici Kargo",
     *     "shop": "ahmetpax.myshopify.com",
     *     "occurred_at": "...",
     *     "event_id": 12345
     *   }
     *
     * Yanıt beklenmez (fire-and-forget) — hata olursa sadece loglanır.
     */
    private function notifyExternalSystem(App $app, Event $event): void
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
                    'event'       => 'app_installed',
                    'app_handle'  => $app->handle,
                    'app_name'    => $app->name,
                    'shop'        => $event->store?->domain,
                    'occurred_at' => $event->created_at?->toIso8601String(),
                    'event_id'    => $event->id,
                ],
            ]);

            Log::info("[install-job] {$app->handle} webhook_url POST: {$app->webhook_url}");
        } catch (\Throwable $e) {
            // Dış sistem geçici kapalıysa job'u fail etme — sadece logla
            Log::warning("[install-job] {$app->handle} webhook_url başarısız: ".$e->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[install-job] tüm denemeler başarısız: event_id={$this->eventId}, hata: ".$exception->getMessage());
    }
}