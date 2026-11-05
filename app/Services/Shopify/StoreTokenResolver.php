<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Shopify\StoreApp;
use App\Services\DeliveryApiClient;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Admin API çağrısından (shop.json vb.) hemen önce mağazanın GÜNCEL Shopify
 * access token'ını uygulamanın kendi backend'inden çeker.
 *
 * Shopify access token'ları ~1 saat geçerli olduğundan veritabanındaki token
 * çoğu zaman bayattır; taze token'ı yalnızca uygulama backend'i verebilir.
 *
 * Davranış:
 *   - App'in get_access_token_endpoint'i tanımlıysa taze token çekilir ve
 *     StoreApp satırına da yazılır (panelin "kurulu" göstergesi token'a bakar).
 *   - API 404 dönerse (mağaza bu uygulamada tanımlı değil) null döner;
 *     bayat token da işe yaramayacağı için fallback kullanılmaz.
 *   - API'ye ulaşılamazsa ya da endpoint tanımsızsa kayıttaki mevcut token'a
 *     düşülür (süresiz offline token kullanan eski uygulamalar çalışmaya devam etsin).
 *
 * Aynı app'e ait DeliveryApiClient örneği bearer token cache'i için saklanır;
 * servis container'da tekil kullanıldığında app başına tek login yapılır.
 */
class StoreTokenResolver
{
    /** @var array<int, DeliveryApiClient> app_id => client (bearer token cache) */
    private array $clients = [];

    public function freshToken(StoreApp $storeApp): ?string
    {
        $app    = $storeApp->app;
        $domain = $storeApp->store?->domain;

        if (! $app || ! $domain || ! $app->api_auth_endpoint || ! $app->get_access_token_endpoint) {
            // Taze token çekilemiyor — kayıttaki (muhtemelen offline) token kullanılır.
            return $storeApp->access_token ?: null;
        }

        try {
            $client = $this->clients[$app->id] ??= new DeliveryApiClient($app);
            $token  = $client->getAccessTokenByShop($domain);
        } catch (Throwable $e) {
            Log::warning("[token-resolver] {$app->handle}@{$domain}: taze token alınamadı — ".$e->getMessage());

            return $storeApp->access_token ?: null;
        }

        if (! $token) {
            Log::info("[token-resolver] {$app->handle}@{$domain}: API token vermedi (mağaza tanımlı değil), bayat token kullanılmayacak");

            return null;
        }

        if ($token !== $storeApp->access_token) {
            $storeApp->access_token = $token;
            $storeApp->save();
        }

        return $token;
    }
}
