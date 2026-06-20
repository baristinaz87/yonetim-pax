<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Shopify\PartnerSyncService;
use Illuminate\Console\Command;
use Throwable;

class ResyncShopifyApp extends Command
{
    protected $signature = 'shopify:resync {handle : App handle değeri}';
    protected $description = 'Bir Shopify uygulamasının tüm geçmişini sıfırlayıp yeniden içe aktarır.';

    public function handle(PartnerSyncService $sync): int
    {
        try {
            $sync->fullResync($this->argument('handle'));
            $this->info("Geçmiş sıfırlandı ve yeniden içe aktarıldı: {$this->argument('handle')}");
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Hata: '.$e->getMessage());
            return self::FAILURE;
        }
    }
}
