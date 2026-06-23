<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Shopify\StoreApp;
use App\Services\Shopify\AdminClient;
use App\Services\Shopify\PartnerSyncService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Mevcut shopify_stores kayıtlarını zenginleştirme komutu.
 *
 * İki kaynaktan veri çeker:
 *   1. Partner API  → shop_owner, contact_email (her zaman)
 *   2. Admin API    → email, phone, country, plan, vb. (token varsa)
 *
 * Kullanım:
 *   php artisan shopify:backfill              → tüm store'lar
 *   php artisan shopify:backfill --only=empty → sadece email/phone'u boş olanlar
 */
class BackfillShopifyStores extends Command
{
    protected $signature = 'shopify:backfill
                            {--only=empty : Boş olanları doldur (empty) | tümünü zorla yenile (all)}';

    protected $description = 'Mevcut Shopify mağazalarını Partner/Admin API ile zenginleştirir.';

    public function handle(PartnerSyncService $sync, AdminClient $admin): int
    {
        try {
            $mode = $this->option('only');

            // 1) Partner API ile üst veri çek — apps üzerinden tüm event'lere bak
            $this->info('Partner API üzerinden store metadata güncelleniyor...');
            $sync->syncAllApps();
            $this->info('Partner sync tamamlandı.');

            // 2) Token olan mağazalar için Admin API'den zenginleştirme
            $query = StoreApp::query()
                ->with('store')
                ->whereNotNull('access_token')
                ->where('status', 'active');

            if ($mode === 'empty') {
                $query->whereHas('store', function ($q) {
                    $q->whereNull('email')->orWhereNull('phone');
                });
            }

            $storeApps = $query->get();
            $this->info("{$storeApps->count()} mağaza Admin API ile zenginleştirilecek.");

            $bar = $this->output->createProgressBar($storeApps->count());
            $bar->start();

            foreach ($storeApps as $sa) {
                if (! $sa->store) {
                    $bar->advance();
                    continue;
                }
                try {
                    $admin->fetchAndUpdateStoreDetails($sa->store->domain, $sa->access_token);
                } catch (Throwable $e) {
                    $this->warn("\n[atlandı] {$sa->store->domain}: ".$e->getMessage());
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();

            $this->info('Backfill tamamlandı.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Hata: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
