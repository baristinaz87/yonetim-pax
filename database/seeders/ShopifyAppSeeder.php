<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Shopify\App;
use Illuminate\Database\Seeder;

/**
 * Shopify Partner hesabındaki uygulamaları shopify_apps tablosuna kayıt eder.
 *
 * shopify_app_gid değerleri Partner Dashboard → GraphQL Explorer'dan
 *   { apps { edges { node { id name handle } } } }
 * sorgusuyla bulunur.
 *
 * Çalıştırma:
 *   php artisan db:seed --class=ShopifyAppSeeder
 */
class ShopifyAppSeeder extends Seeder
{
    public function run(): void
    {
        $apps = [
            [
             'name'            => 'Yurtici Kargo',
             'handle'          => 'yurtici-kargo',
             'shopify_app_gid' => 'gid://partners/App/4645385',
            ],
            [
                'name'            => 'Hepsijet Kargo',
                'handle'          => 'hepsijet-kargo',
                'shopify_app_gid' => 'gid://partners/App/258474180609',
            ],
            [
                'name'            => 'Kapıda Ödeme Pro',
                'handle'          => 'kapida-odeme-pro',
                'shopify_app_gid' => 'gid://partners/App/159124062209',
            ],
            [
                'name'            => 'DHL Kargo',
                'handle'          => 'dhl-kargo',
                'shopify_app_gid' => 'gid://partners/App/4757913',
            ],
            [
                'name'            => 'Aras Kargo',
                'handle'          => 'aras-kargo',
                'shopify_app_gid' => 'gid://partners/App/4795773',
            ],
            [
                'name'            => 'Efatura',
                'handle'          => 'efatura',
                'shopify_app_gid' => 'gid://partners/App/5918557',
            ]
        ];

        if (empty($apps)) {
            $this->command->warn('ShopifyAppSeeder: $apps dizisi boş. Lütfen uygulama kayıtlarını ekleyin.');
            return;
        }

        $count = 0;
        foreach ($apps as $data) {
            $handle = $data['handle'];
            $envKey = strtoupper(str_replace('-', '_', $handle));

            $clientId     = env("SHOPIFY_APP_CLIENT_ID_{$envKey}");
            $clientSecret = env("SHOPIFY_APP_CLIENT_SECRET_{$envKey}");

            if (! $clientId || ! $clientSecret) {
                $this->command->warn("Atlandı [{$handle}]: SHOPIFY_APP_CLIENT_ID_{$envKey} veya SHOPIFY_APP_CLIENT_SECRET_{$envKey} .env'de eksik.");
                continue;
            }

            App::updateOrCreate(
                ['handle' => $handle],
                array_merge($data, [
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                ]),
            );
            $count++;
        }

        $this->command->info("{$count} Shopify app kayıt edildi / güncellendi.");
    }
}
