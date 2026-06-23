<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Shopify\App;
use App\Models\Shopify\PartnerAccount;
use Illuminate\Database\Seeder;

/**
 * Shopify Partner hesaplarındaki uygulamaları shopify_apps tablosuna kayıt eder.
 *
 * shopify_app_gid değerleri Partner Dashboard → GraphQL Explorer'dan
 *   { apps { edges { node { id name handle } } } }
 * sorgusuyla bulunur.
 *
 * Her uygulama, partner_account_id sütunu üzerinden bir Partner hesabına bağlanır.
 * partner_account_key alanı ile org_id eşleştirmesi yapılır:
 *   - 'default' → tablodaki ilk aktif partner hesabı
 *   - 'foo'      → SHOPIFY_PARTNER_ORG_ID_FOO
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
                'name'                => 'Yurtici Kargo',
                'handle'              => 'yurtici-kargo',
                'shopify_app_gid'     => 'gid://partners/App/4645385',
                'partner_account_key' => 'default',
            ],
            [
                'name'                => 'Hepsijet Kargo',
                'handle'              => 'hepsijet-kargo',
                'shopify_app_gid'     => 'gid://partners/App/258474180609',
                'partner_account_key' => 'default',
            ],
            [
                'name'                => 'Kapıda Ödeme Pro',
                'handle'              => 'kapida-odeme-pro',
                'shopify_app_gid'     => 'gid://partners/App/159124062209',
                'partner_account_key' => 'default',
            ],
            [
                'name'                => 'DHL Kargo',
                'handle'              => 'dhl-kargo',
                'shopify_app_gid'     => 'gid://partners/App/4757913',
                'partner_account_key' => 'default',
            ],
            [
                'name'                => 'Aras Kargo',
                'handle'              => 'aras-kargo',
                'shopify_app_gid'     => 'gid://partners/App/4795773',
                'partner_account_key' => 'default',
            ],
            [
                'name'                => 'Efatura',
                'handle'              => 'efatura',
                'shopify_app_gid'     => 'gid://partners/App/5918557',
                'partner_account_key' => 'default',
            ],
        ];

        if (empty($apps)) {
            $this->command->warn('ShopifyAppSeeder: $apps dizisi boş. Lütfen uygulama kayıtlarını ekleyin.');
            return;
        }

        // 'default' anahtarını ilk aktif partner hesabına çöz
        $defaultPartner = PartnerAccount::query()->active()->orderBy('id')->first();
        if (! $defaultPartner) {
            $this->command->error('ShopifyAppSeeder: Aktif partner hesabı bulunamadı. Önce ShopifyPartnerAccountSeeder çalıştırın.');
            return;
        }

        $count = 0;
        foreach ($apps as $data) {
            $handle = $data['handle'];
            $envKey = strtoupper(str_replace('-', '_', $handle));
            $key    = $data['partner_account_key'] ?? 'default';

            $clientId     = env("SHOPIFY_APP_CLIENT_ID_{$envKey}");
            $clientSecret = env("SHOPIFY_APP_CLIENT_SECRET_{$envKey}");

            if (! $clientId || ! $clientSecret) {
                $this->command->warn("Atlandı [{$handle}]: SHOPIFY_APP_CLIENT_ID_{$envKey} veya SHOPIFY_APP_CLIENT_SECRET_{$envKey} .env'de eksik.");
                continue;
            }

            // partner_account_key → org_id eşleştirmesi
            $partner = $this->resolvePartner($key, $defaultPartner);

            // partner_account_key gerçek bir kolon değil, sadece eşleştirme için kullanılıyor —
            // veritabanına yazılmadan önce $data'dan çıkar.
            unset($data['partner_account_key']);

            App::updateOrCreate(
                ['handle' => $handle],
                array_merge($data, [
                    'partner_account_id' => $partner?->id,
                    'client_id'          => $clientId,
                    'client_secret'      => $clientSecret,
                ]),
            );
            $count++;
        }

        $this->command->info("{$count} Shopify app kayıt edildi / güncellendi.");
    }

    /**
     * partner_account_key değerini bir PartnerAccount örneğine çöz.
     *
     * - 'default'         → ilk aktif partner hesabı
     * - 'foo'             → SHOPIFY_PARTNER_ORG_ID_FOO .env değerinden aranır
     * - org_id direkt değer (ör: '1779760') → doğrudan db'de aranır
     */
    private function resolvePartner(string $key, PartnerAccount $fallback): ?PartnerAccount
    {
        if ($key === 'default' || $key === '') {
            return $fallback;
        }

        // Direkt org_id (sayısal) verilmiş olabilir
        if (ctype_digit($key)) {
            return PartnerAccount::where('org_id', $key)->first() ?? $fallback;
        }

        // .env'den SHOPIFY_PARTNER_ORG_ID_<KEY> oku
        $envKey   = strtoupper($key);
        $envOrgId = env("SHOPIFY_PARTNER_ORG_ID_{$envKey}");

        if ($envOrgId) {
            $found = PartnerAccount::where('org_id', $envOrgId)->first();
            if ($found) {
                return $found;
            }
            $this->command->warn("[{$key}] için SHOPIFY_PARTNER_ORG_ID_{$envKey}={$envOrgId} .env'de bulundu ama DB'de karşılığı yok. Default hesap kullanılacak.");
        } else {
            $this->command->warn("[{$key}] için SHOPIFY_PARTNER_ORG_ID_{$envKey} .env'de tanımlı değil. Default hesap kullanılacak.");
        }

        return $fallback;
    }
}