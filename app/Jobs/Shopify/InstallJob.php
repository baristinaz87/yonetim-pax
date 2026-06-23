<?php

declare(strict_types=1);

namespace App\Jobs\Shopify;

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
 *   1. App'in API konfigürasyonu ile login ol (bearer token al)
 *   2. get_access_token_endpoint?shop=... üzerinden Shopify access token çek
 *   3. StoreApp tablosuna token'ı yaz
 *   4. Admin API shop.json çağrısı ile Store satırını güncelle
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

        // 1) API client oluştur (App'in api_auth_endpoint / get_access_token_endpoint
        //    sütunlarından okur)
        try {
            $api = new DeliveryApiClient($app);
        } catch (\Throwable $e) {
            // API konfigürasyonu eksik → sadece logla, job'u fail etme.
            Log::warning("[install-job] {$app->handle}: API client oluşturulamadı — ".$e->getMessage());
            return;
        }

        // 2) Login + access token çek
        try {
            $accessToken = $api->getAccessTokenByShop($domain);
        } catch (\Throwable $e) {
            // API tamamen kapalı — job'u yeniden denensin diye exception fırlat
            Log::error("[install-job] {$app->handle} API hatası: ".$e->getMessage());
            throw $e;
        }

        if (! $accessToken) {
            // Bu app bu mağaza için API'de tanımlı değil — normal bir durum
            Log::info("[install-job] {$app->handle}: {$domain} için access token alınamadı, atlandı");
            return;
        }

        // 3) StoreApp tablosuna token'ı yaz
        StoreApp::updateOrCreate(
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

    public function failed(\Throwable $exception): void
    {
        Log::error("[install-job] tüm denemeler başarısız: event_id={$this->eventId}, hata: ".$exception->getMessage());
    }
}