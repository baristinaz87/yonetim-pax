<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Shopify\PartnerAccount;
use Illuminate\Database\Seeder;

/**
 * .env'deki Shopify Partner kimlik bilgilerini shopify_partner_accounts tablosuna taşır.
 *
 * Bu seeder idempotenttir: aynı org_id ile kayıt varsa günceller, yoksa oluşturur.
 *
 * Çalıştırma:
 *   php artisan db:seed --class=ShopifyPartnerAccountSeeder
 *
 * .env'de beklenen değişkenler:
 *   SHOPIFY_PARTNER_ORG_ID
 *   SHOPIFY_PARTNER_TOKEN
 *   SHOPIFY_API_VERSION       (opsiyonel, varsayılan 2026-04)
 *   SHOPIFY_PARTNER_NAME      (opsiyonel, varsayılan "Default Partner")
 */
class ShopifyPartnerAccountSeeder extends Seeder
{
    public function run(): void
    {
        $orgId      = env('SHOPIFY_PARTNER_ORG_ID');
        $token      = env('SHOPIFY_PARTNER_TOKEN');
        $apiVersion = env('SHOPIFY_API_VERSION', '2026-04');
        $name       = env('SHOPIFY_PARTNER_NAME', 'Default Partner');

        if (! $orgId || ! $token) {
            $this->command->warn('ShopifyPartnerAccountSeeder: SHOPIFY_PARTNER_ORG_ID veya SHOPIFY_PARTNER_TOKEN .env\'de tanımlı değil — atlandı.');
            $this->command->warn('Yeni bir partner hesabı eklemek için: php artisan db:seed veya Shopify yönetim panelini kullanın.');
            return;
        }

        $account = PartnerAccount::updateOrCreate(
            ['org_id' => $orgId],
            [
                'name'         => $name,
                'access_token' => $token,
                'api_version'  => $apiVersion,
                'active'       => true,
                'notes'        => '.env üzerinden taşındı',
            ],
        );

        $this->command->info("Partner hesabı kayıt edildi: {$account->name} (org_id={$account->org_id}, id={$account->id})");
        $this->command->info("Artık uygulamaları bu hesaba bağlamak için Shopify → Uygulamalar sayfasını kullanabilirsiniz.");
    }
}