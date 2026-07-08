<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Shopify\StoreApp;
use App\Models\Shopify\StoreAppData;
use App\Services\DeliveryApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Aktif mağaza-uygulama kayıtları için `get_app_data_endpoint` üzerinden
 * uygulamaya özel ham veriyi çeker ve `shopify_store_app_data` tablosuna yazar.
 *
 * Mantık:
 *   - `shopify_store_apps` tablosunda status='active' olan
 *     VE `access_token` dolu olan satırlar hedeflenir.
 *   - App'in `get_app_data_endpoint`'i tanımlıysa ilgili mağaza için
 *     delivery API'ye POST atılır; dönen JSON StoreAppData üzerinden saklanır.
 *   - Endpoint boşsa kayıt sessizce atlanır (tıpkı `get_access_token_endpoint`
 *     boşsa ShopifyFixShopInformations komutunda kaydın atlanması gibi).
 *   - Token'ı olmayan (henüz kurulmamış/refresh edilmemiş) kayıtlar skip edilir
 *     — bu komutun amacı sadece app data tazelemektir, token yenilemez.
 *     Token yenileme için `shopify:fix-shop-informations` kullanılmalıdır.
 *
 * Kullanım:
 *   php artisan shopify:update-app-shop-data
 *   php artisan shopify:update-app-shop-data --app=foo
 *   php artisan shopify:update-app-shop-data --store=bar.myshopify.com
 *   php artisan shopify:update-app-shop-data --dry-run
 */
class UpdateAppShopData extends Command
{
    /** Aynı app için ardışık istekler arasındaki minimum bekleme (ms). */
    public const DEFAULT_MIN_DELAY_MS = 250;

    protected $signature = 'shopify:update-app-shop-data
                            {--app=    : Sadece bu handle için çalış}
                            {--store=  : Sadece bu mağaza domain için çalış}
                            {--dry-run : Veritabanını güncellemeden sadece raporla}
                            {--delay-ms= : Aynı app için ardışık istekler arası min bekleme (ms). Varsayılan: 250, 0=kapalı}';

    protected $description = 'Aktif mağaza-uygulama kayıtları için app data endpoint\'inden gelen JSON verisini shopify_store_app_data tablosuna yazar.';

    public function handle(): int
    {
        $dryRun     = (bool) $this->option('dry-run');
        $appOpt     = $this->option('app');
        $storeOpt   = $this->option('store');
        $minDelayMs = (int) $this->option('delay-ms') ?: self::DEFAULT_MIN_DELAY_MS;

        if ($minDelayMs > 0) {
            $this->info("App başına minimum istek aralığı: {$minDelayMs}ms");
        }
        if ($dryRun) {
            $this->warn('DRY-RUN modu: veritabanı güncellenmeyecek.');
        }

        // Hedef kayıtları seç: status='active' VE access_token dolu.
        // Token yenileme bu komutun işi değil — token'ı olmayan kayıtlar
        // skip edilir (kullanıcı isterse önce shopify:fix-shop-informations çalıştırır).
        $query = StoreApp::query()
            ->with(['store:id,domain', 'app'])
            ->where('status', 'active')
            ->whereNotNull('access_token')
            ->where('access_token', '!=', '');

        if ($appOpt) {
            $query->whereHas('app', fn ($q) => $q->where('handle', $appOpt));
        }
        if ($storeOpt) {
            $query->whereHas('store', fn ($q) => $q->where('domain', $storeOpt));
        }

        $records = $query->orderBy('app_id')->orderBy('store_id')->get();

        if ($records->isEmpty()) {
            $this->info('İşlenecek aktif kayıt bulunamadı.');
            return self::SUCCESS;
        }

        $this->info("Hedef kayıt sayısı: {$records->count()}");

        // App başına DeliveryApiClient'ı bir kez örnekleyelim (token cache için).
        $clientCache = [];

        $stats = [
            'fetched'    => 0,
            'skipped'    => 0,
            'failed'     => 0,
            'noToken'    => 0,
        ];

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        $lastRequestAt = []; // app_id => son istek zamanı (unix ms)

        foreach ($records as $sa) {
            $bar->advance();

            $domain = $sa->store?->domain;
            $app    = $sa->app;
            $token  = $sa->access_token;

            if (! $domain || ! $app) {
                $stats['skipped']++;
                Log::warning("[update-app-data] store/app ilişkisi eksik: store_app_id={$sa->id}");
                continue;
            }

            // Token güvenlik kontrolü — sorgu bunu zaten filtreliyor ama
            // veri bütünlüğü için burada da doğrulayalım.
            if (! $token) {
                $stats['noToken']++;
                continue;
            }

            // Bu app için API konfigürasyonu var mı?
            // DeliveryApiClient constructor'ı api_auth_endpoint + get_access_token_endpoint'i
            // zorunlu tutar; ancak app_data endpoint'i opsiyoneldir.
            if (! $app->api_auth_endpoint || ! $app->get_access_token_endpoint) {
                $stats['skipped']++;
                Log::warning("[update-app-data] {$app->handle}: API endpoint tanımsız, atlandı");
                continue;
            }

            // App data endpoint tanımsız → bu adım sessizce atlanır.
            // (GetAppDataByShop() da bunu kontrol ediyor ama erken çıkmak
            //  gereksiz DeliveryApiClient örneklemeyi engeller.)
            if (! $app->get_app_data_endpoint) {
                $stats['skipped']++;
                Log::info("[update-app-data] {$app->handle}: get_app_data_endpoint tanımsız, atlandı");
                continue;
            }

            try {
                if (! isset($clientCache[$app->id])) {
                    $clientCache[$app->id] = new DeliveryApiClient($app);
                }
                $api = $clientCache[$app->id];

                // Aynı app'e ardışık isteklerde proaktif throttle.
                if ($minDelayMs > 0 && isset($lastRequestAt[$app->id])) {
                    $elapsedMs = (int) ((microtime(true) - $lastRequestAt[$app->id]) * 1000);
                    if ($elapsedMs < $minDelayMs) {
                        usleep(($minDelayMs - $elapsedMs) * 1000);
                    }
                }

                $appData = $api->getAppDataByShop($domain, $token);
                $lastRequestAt[$app->id] = microtime(true);

                if ($appData !== null) {
                    if (! $dryRun) {
                        StoreAppData::updateOrCreate(
                            ['store_id' => $sa->store_id, 'app_id' => $sa->app_id],
                            ['data'     => $appData],
                        );
                    }
                    $stats['fetched']++;
                    Log::info("[update-app-data] {$app->handle}@{$domain}: app data alındı ve kaydedildi");
                } else {
                    // Endpoint tanımlı ama yanıt boş/404 — sayıyoruz.
                    $stats['skipped']++;
                    Log::info("[update-app-data] {$app->handle}@{$domain}: yanıt boş/404 — atlandı");
                }
            } catch (Throwable $e) {
                $stats['failed']++;
                Log::error("[update-app-data] {$app->handle}@{$domain}: hata — ".$e->getMessage());
                continue;
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Sonuç:');
        $this->table(
            ['Sonuç', 'Adet'],
            [
                ['App data alındı',               $stats['fetched']],
                ['Atlanan (endpoint yok/yanıt boş)',$stats['skipped']],
                ['Token yok (önce fix-shop-informations çalıştırın)',$stats['noToken']],
                ['API hatası',                    $stats['failed']],
            ],
        );

        return self::SUCCESS;
    }
}