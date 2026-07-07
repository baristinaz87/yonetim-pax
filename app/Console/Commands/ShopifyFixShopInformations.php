<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Shopify\StoreApp;
use App\Services\DeliveryApiClient;
use App\Services\Shopify\AdminClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mevcut mağaza-uygulama kayıtları için eksik access_token'ları toplar.
 *
 * Mantık:
 *   - `shopify_store_apps` tablosunda status='active' olan
 *     ama access_token NULL/boş olan satırlar hedeflenir.
 *   - Her satır için ilgili App'in `get_access_token_endpoint`'ine
 *     shop domain ile istek atılır, dönen token yazılır.
 *   - API çağrısı başarısız olan veya bu hesapta tanımlı olmayan
 *     mağazalar "kaldırılmış" olarak kabul edilip status='uninstalled'
 *     olarak işaretlenir (erişilemeyen token → kurulu değil).
 *   - Token başarıyla alınan mağazalar için Shopify Admin API shop.json
 *     çağrısı yapılarak `shopify_stores` tablosu zenginleştirilir
 *     (email, phone, plan, address vb.).
 *
 * Kullanım:
 *   php artisan shopify:fix-shop-informations
 *   php artisan shopify:fix-shop-informations --app=foo
 *   php artisan shopify:fix-shop-informations --store=bar.myshopify.com
 *   php artisan shopify:fix-shop-informations --dry-run
 */
class ShopifyFixShopInformations extends Command
{
    /** Aynı app için ardışık istekler arasındaki minimum bekleme (ms). */
    public const DEFAULT_MIN_DELAY_MS = 250;

    protected $signature = 'shopify:fix-shop-informations
                            {--app=    : Sadece bu handle için çalış}
                            {--store=  : Sadece bu mağaza domain için çalış}
                            {--dry-run : Veritabanını güncellemeden sadece raporla}
                            {--skip-store-enrich : Token yenileme sonrası shop.json zenginleştirmesini atla}
                            {--delay-ms= : Aynı app için ardışık istekler arası min bekleme (ms). Varsayılan: 250, 0=kapalı}';

    protected $description = 'Aktif mağaza-uygulama kayıtları için eksik Shopify access token\'larını doldurur ve mağaza bilgilerini zenginleştirir.';

    public function handle(AdminClient $admin): int
    {
        $dryRun         = (bool) $this->option('dry-run');
        $appOpt         = $this->option('app');
        $storeOpt       = $this->option('store');
        $skipStoreEnrich = (bool) $this->option('skip-store-enrich');
        $minDelayMs     = (int) $this->option('delay-ms') ?: self::DEFAULT_MIN_DELAY_MS;

        if ($minDelayMs > 0) {
            $this->info("App başına minimum istek aralığı: {$minDelayMs}ms");
        }

        if ($dryRun) {
            $this->warn('DRY-RUN modu: veritabanı güncellenmeyecek.');
        }
        if ($skipStoreEnrich) {
            $this->warn('--skip-store-enrich: shop.json zenginleştirmesi atlanacak.');
        }

        // Hedef kayıtları seç: status='active' ama access_token boş/NULL.
        $query = StoreApp::query()
            ->with(['store:id,domain', 'app'])
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('access_token')->orWhere('access_token', '');
            });

        if ($appOpt) {
            $query->whereHas('app', fn ($q) => $q->where('handle', $appOpt));
        }

        if ($storeOpt) {
            $query->whereHas('store', fn ($q) => $q->where('domain', $storeOpt));
        }

        $records = $query->orderBy('store_id')->orderBy('app_id')->get();

        if ($records->isEmpty()) {
            $this->info('Eksik access token olan kayıt bulunamadı.');
            return self::SUCCESS;
        }

        $this->info("Hedef kayıt sayısı: {$records->count()}");

        // App başına DeliveryApiClient'ı bir kez örnekleyelim (token cache için).
        $clientCache = [];

        // Mağaza başına sadece bir kez shop.json çağrısı yapmak için
        // zenginleştirilmiş domain'leri takip ediyoruz.
        $enrichedDomains = [];

        $stats = [
            'fixed'         => 0,
            'uninstall'     => 0,
            'skipped'       => 0,
            'failed'        => 0,
            'storesEnriched'=> 0,
            'storesFailed'  => 0,
        ];

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        $lastRequestAt = []; // app_id => son istek zamanı (unix ms)

        foreach ($records as $sa) {
            $bar->advance();

            $domain = $sa->store?->domain;
            $app    = $sa->app;

            if (! $domain || ! $app) {
                $stats['skipped']++;
                Log::warning("[refresh-tokens] store/app ilişkisi eksik: store_app_id={$sa->id}");
                continue;
            }

            // Bu app için API konfigürasyonu var mı?
            if (! $app->api_auth_endpoint || ! $app->get_access_token_endpoint) {
                $stats['skipped']++;
                Log::warning("[refresh-tokens] {$app->handle}: API endpoint tanımsız, atlandı");
                continue;
            }

            try {
                if (! isset($clientCache[$app->id])) {
                    $clientCache[$app->id] = new DeliveryApiClient($app);
                }
                $api = $clientCache[$app->id];

                // Aynı app'e ardışık isteklerde proaktif throttle.
                // DeliveryApiClient zaten 429 dönerse retry-after uyguluyor;
                // buradaki bekleme 429 OLUŞMADAN yavaşlatma sağlar.
                if ($minDelayMs > 0 && isset($lastRequestAt[$app->id])) {
                    $elapsedMs = (int) ((microtime(true) - $lastRequestAt[$app->id]) * 1000);
                    if ($elapsedMs < $minDelayMs) {
                        usleep(($minDelayMs - $elapsedMs) * 1000);
                    }
                }

                $token = $api->getAccessTokenByShop($domain);
                $lastRequestAt[$app->id] = microtime(true);
            } catch (Throwable $e) {
                $stats['failed']++;
                Log::error("[refresh-tokens] {$app->handle}@{$domain}: API hatası — ".$e->getMessage());
                continue;
            }

            if ($token) {
                if (! $dryRun) {
                    $sa->access_token = $token;
                    $sa->save();
                }
                $stats['fixed']++;
                Log::info("[refresh-tokens] {$app->handle}@{$domain}: token yazıldı");

                // Mağaza zenginleştirmesi — domain başına sadece bir kez.
                if (! $skipStoreEnrich && ! isset($enrichedDomains[$domain])) {
                    $enrichedDomains[$domain] = true;
                    try {
                        $store = $admin->fetchAndUpdateStoreDetails($domain, $token);
                        if ($store) {
                            $stats['storesEnriched']++;
                            Log::info("[refresh-tokens] {$domain}: store bilgileri güncellendi");
                        } else {
                            $stats['storesFailed']++;
                            Log::warning("[refresh-tokens] {$domain}: store güncellenemedi (shop.json boş/mağaza bulunamadı)");
                        }
                    } catch (Throwable $e) {
                        $stats['storesFailed']++;
                        Log::error("[refresh-tokens] {$domain} admin API hatası: ".$e->getMessage());
                    }
                }
            } else {
                // API'ye göre bu mağaza bu hesapta tanımlı değil (404) →
                // kaldırılmış kabul et ve status'u güncelle.
                if (! $dryRun) {
                    $sa->status          = 'uninstalled';
                    $sa->uninstalled_at  = $sa->uninstalled_at ?? now();
                    $sa->save();
                }
                $stats['uninstall']++;
                Log::info("[refresh-tokens] {$app->handle}@{$domain}: token alınamadı → uninstalled olarak işaretlendi");
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Sonuç:');
        $this->table(
            ['Sonuç', 'Adet'],
            [
                ['Token yazılan (düzeltildi)',     $stats['fixed']],
                ['Uninstalled olarak işaretlenen',$stats['uninstall']],
                ['Atlanan (konfigürasyon eksik)', $stats['skipped']],
                ['Token/API hatası',              $stats['failed']],
                ['Mağaza zenginleştirildi',       $stats['storesEnriched']],
                ['Mağaza zenginleştirme hatası',  $stats['storesFailed']],
            ],
        );

        return self::SUCCESS;
    }
}
