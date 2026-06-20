<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Shopify\Store;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Shopify Admin API istemcisi.
 * Mağaza zenginleştirme (shop.json) için kullanılır.
 *
 * Endpoint: https://{domain}/admin/api/2026-04/shop.json
 * Auth:     X-Shopify-Access-Token header'ı (mağazaya özel).
 */
class AdminClient
{
    private Client $http;
    private string $apiVersion;

    public function __construct(string $apiVersion = '2026-04')
    {
        $this->apiVersion = $apiVersion;
        $this->http = new Client([
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 20,
        ]);
    }

    /**
     * Mağazanın shop.json'unu çek ve veritabanına yaz.
     */
    public function fetchAndUpdateStoreDetails(string $domain, ?string $accessToken): ?Store
    {
        if (! $accessToken) {
            return null;
        }

        try {
            $response = $this->http->get("https://{$domain}/admin/api/{$this->apiVersion}/shop.json", [
                'headers' => [
                    'X-Shopify-Access-Token' => $accessToken,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                Log::warning("[shopify-admin] {$domain} shop.json HTTP {$response->getStatusCode()}");
                return null;
            }

            $payload = json_decode((string) $response->getBody(), true);
            $shop    = $payload['shop'] ?? null;

            if (! $shop) {
                return null;
            }

            $store = Store::where('domain', $domain)->first();
            if (! $store) {
                return null;
            }

            $store->fill([
                'shop_id'          => (string) ($shop['id'] ?? ''),
                'name'             => $shop['name'] ?? null,
                'shop_owner'       => $shop['shop_owner'] ?? null,
                'email'            => $shop['email'] ?? null,
                'phone'            => $shop['phone'] ?? null,
                'address1'         => $shop['address1'] ?? null,
                'city'             => $shop['city'] ?? null,
                'zip'              => $shop['zip'] ?? null,
                'country'          => $shop['country_name'] ?? null,
                'country_code'     => $shop['country_code'] ?? null,
                'currency'         => $shop['currency'] ?? null,
                'plan_name'        => $shop['plan_name'] ?? null,
                'plan_display_name'=> $shop['plan_display_name'] ?? null,
                'timezone'         => $shop['iana_timezone'] ?? null,
                'language'         => $shop['primary_locale'] ?? null,
            ])->save();

            Log::info("[shopify-admin] {$domain} bilgileri güncellendi");
            return $store;
        } catch (GuzzleException $e) {
            Log::error("[shopify-admin] {$domain} hata: ".$e->getMessage());
            return null;
        }
    }
}
