<?php

declare(strict_types=1);

namespace App\Jobs\Shopify;

use App\Models\Shopify\Event;
use App\Models\Shopify\StoreApp;
use App\Models\Shopify\StoreAppData;
use App\Services\DeliveryApiClient;
use App\Services\Shopify\AdminClient;
use App\Services\Shopify\FlowTransactionCreator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Shopify "app installed" event'i için arka plan işi.
 *
 * Akış:
 *   1. App'in API konfigürasyonu ile login ol (bearer token al)
 *   2. get_access_token_endpoint?shop=... üzerinden Shopify access token çek
 *      (alınamazsa StoreApp'te mevcut token — ör. webhook'tan gelen — kullanılır)
 *   3. StoreApp tablosuna token'ı yaz
 *   4. App data endpoint'i tanımlıysa app data'yı çek (opsiyonel)
 *   5. Admin API shop.json çağrısı ile Store satırını güncelle
 *   6. Event'in flow transaction'larını aç (kurulum maili vb.)
 *
 * Yeniden deneme:
 *   - Token veya shop.json alınamazsa ya da API hata verirse job
 *     1, 2, 5, 10, 15 dk arayla toplam 6 kez denenir.
 *   - Deneme hakkı bitince flow transaction'lar yine de açılır;
 *     iletişim bilgisi olmayan kanal için transaction açılmaz.
 *   - `sync` kuyrukta release çalışmadığı için yeniden deneme yapılmaz.
 */
class InstallJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 6;
    public int $timeout = 90;

    public function backoff(): array
    {
        return [60, 120, 300, 600, 900];
    }

    public function __construct(public int $eventId) {}

    public function handle(AdminClient $admin, FlowTransactionCreator $flowTransactions): void
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
            $flowTransactions->createForEvent($event);
            return;
        }

        $domain = $event->store->domain;
        $app    = $event->app;

        Log::info("[install-job] başladı: app={$app->handle}, store={$domain}, event_id={$event->id}, deneme={$this->attempts()}");

        // 1) API client oluştur (App'in api_auth_endpoint / get_access_token_endpoint
        //    sütunlarından okur)
        $api = null;
        try {
            $api = new DeliveryApiClient($app);
        } catch (\Throwable $e) {
            // API konfigürasyonu eksik → mevcut token varsa onunla devam edilir.
            Log::warning("[install-job] {$app->handle}: API client oluşturulamadı — ".$e->getMessage());
        }

        // 2) Login + access token çek
        $accessToken = null;
        if ($api) {
            try {
                $accessToken = $api->getAccessTokenByShop($domain);
            } catch (\Throwable $e) {
                // API tamamen kapalı — job'u yeniden denensin diye exception fırlat
                Log::error("[install-job] {$app->handle} API hatası: ".$e->getMessage());
                throw $e;
            }
        }

        // API'den token gelmediyse webhook vb. ile önceden yazılmış token'ı kullan.
        $accessToken ??= StoreApp::query()
            ->where('store_id', $event->store->id)
            ->where('app_id', $app->id)
            ->value('access_token') ?: null;

        if (! $accessToken) {
            // Uygulama backend'i token'ı henüz kaydetmemiş olabilir — biraz sonra tekrar dene.
            $this->retryOrGiveUp($event, $flowTransactions, "{$domain} için access token alınamadı");
            return;
        }

        // 3) StoreApp tablosuna token'ı yaz. Daha yeni bir kurulum/kaldırma
        //    biliniyorsa (event geçmişe aitse) durum alanlarına dokunma.
        $installedAt = $event->created_at ?? now();
        $storeApp    = StoreApp::firstOrNew([
            'store_id' => $event->store->id,
            'app_id'   => $app->id,
        ]);

        if (! $storeApp->exists || ! $storeApp->changedAfter($installedAt)) {
            $storeApp->fill([
                'status'         => 'active',
                'installed_at'   => $installedAt,
                'uninstalled_at' => null,
            ]);
        }

        $storeApp->access_token = $accessToken;
        $storeApp->save();

        Log::info("[install-job] {$domain} → {$app->handle}: access token yazıldı");

        // 4) App data (opsiyonel) — flow'lar e-posta/telefonu buradan da okur.
        if ($api) {
            try {
                $appData = $api->getAppDataByShop($domain, $accessToken);
                if ($appData !== null) {
                    StoreAppData::updateOrCreate(
                        ['store_id' => $event->store->id, 'app_id' => $app->id],
                        ['data' => $appData],
                    );
                }
            } catch (\Throwable $e) {
                Log::warning("[install-job] {$domain} app data alınamadı: ".$e->getMessage());
            }
        }

        // 5) Admin API ile shop bilgilerini çek ve Store'u güncelle
        if (! $admin->fetchAndUpdateStoreDetails($domain, $accessToken)) {
            $this->retryOrGiveUp($event, $flowTransactions, "{$domain} admin shop.json alınamadı");
            return;
        }

        // 6) Mağaza bilgileri hazır — kurulum flow'larını aç.
        $flowTransactions->createForEvent($event);

        Log::info("[install-job] tamamlandı: event_id={$event->id}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[install-job] tüm denemeler başarısız: event_id={$this->eventId}, hata: ".$exception->getMessage());

        // Mağaza bilgileri toplanamasa da flow transaction'lar açılsın;
        // böylece durum panelde görünür ve elle tekrar denenebilir.
        $event = Event::find($this->eventId);
        if ($event) {
            app(FlowTransactionCreator::class)->createForEvent($event);
        }
    }

    /**
     * Deneme hakkı varsa job'u backoff süresi kadar sonra tekrar kuyruğa alır,
     * yoksa flow transaction'ları mevcut bilgilerle açar.
     */
    private function retryOrGiveUp(Event $event, FlowTransactionCreator $flowTransactions, string $reason): void
    {
        $canRetry = $this->job
            && ! $this->job instanceof SyncJob
            && $this->attempts() < $this->tries;

        if ($canRetry) {
            $delay = $this->backoff()[$this->attempts() - 1] ?? 900;
            Log::info("[install-job] {$reason}, {$delay}s sonra tekrar denenecek (deneme {$this->attempts()}/{$this->tries})");
            $this->release($delay);
            return;
        }

        Log::warning("[install-job] {$reason}, tekrar denenmeyecek — flow transaction'lar mevcut bilgilerle açılıyor");
        $flowTransactions->createForEvent($event);
    }
}
