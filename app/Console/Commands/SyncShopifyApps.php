<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Shopify\App;
use App\Services\Shopify\PartnerSyncService;
use Illuminate\Console\Command;
use Throwable;

/**
 * php artisan shopify:sync                 → tüm aktif app'leri senkronize et
 * php artisan shopify:sync --handle=foo    → tek bir app'i senkronize et
 * php artisan shopify:resync foo           → bir app'in tüm geçmişini sıfırlayıp yeniden çek
 */
class SyncShopifyApps extends Command
{
    protected $signature = 'shopify:sync
                            {--handle= : Sadece bu handle için sync yap}';

    protected $description = 'Shopify Partner API üzerinden uygulama install/uninstall eventlerini senkronize eder.';

    public function handle(PartnerSyncService $sync): int
    {
        try {
            $handle = $this->option('handle');

            if ($handle) {
                $app = App::where('handle', $handle)->first();
                if (! $app) {
                    $this->error("App bulunamadı: {$handle}");
                    return self::FAILURE;
                }
                $count = $sync->syncApp($app);
                $this->info("{$app->name}: {$count} yeni olay işlendi.");
                return self::SUCCESS;
            }

            $sync->syncAllApps();
            $this->info('Tüm aktif uygulamalar senkronize edildi.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Hata: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
