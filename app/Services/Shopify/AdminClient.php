<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Shopify\Store;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Shopify Admin API istemcisi.
 * Mağaza zenginleştirme (shop.json) için kullanılır.
 *
 * Endpoint: https://{domain}/admin/api/2026-04/shop.json
 * Auth:     X-Shopify-Access-Token header'ı (mağazaya özel).
 *
 * Shopify REST Admin API "leaky bucket" rate limiting uygular.
 * 429 veya 503 throttle durumunda Retry-After header'ına göre bekleyip
 * tekrar denenir.
 */
class AdminClient
{
    private Client $http;
    private string $apiVersion;

    private const MAX_RETRIES_THROTTLE = 5;
    private const INITIAL_BACKOFF = 2;

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

        $attempt = 0;
        while (true) {
            try {
                $response = $this->http->get("https://{$domain}/admin/api/{$this->apiVersion}/shop.json", [
                    'headers' => [
                        'X-Shopify-Access-Token' => $accessToken,
                    ],
                ]);
                break; // başarılı
            } catch (ClientException $e) {
                $status = $e->getResponse()?->getStatusCode();

                // Shopify throttle: 429 (rate limit) veya 503 (transient)
                if ($status === 429 || $status === 503) {
                    $attempt++;
                    if ($attempt > self::MAX_RETRIES_THROTTLE) {
                        Log::error("[shopify-admin] {$domain} throttle aşıldı ({$attempt} deneme, status={$status})");
                        return null;
                    }
                    $wait = $this->getThrottleDelay($e, $attempt);
                    Log::warning("[shopify-admin] {$domain} throttle ({$status}), {$wait}s bekleniyor (deneme {$attempt}/".self::MAX_RETRIES_THROTTLE.')');
                    sleep($wait);
                    continue;
                }

                // 401/403 → token geçersiz
                if ($status === 401 || $status === 403) {
                    Log::warning("[shopify-admin] {$domain} token reddedildi ({$status})");
                    return null;
                }

                Log::warning("[shopify-admin] {$domain} shop.json HTTP {$status}: ".$e->getMessage());
                return null;
            } catch (GuzzleException $e) {
                Log::error("[shopify-admin] {$domain} hata: ".$e->getMessage());
                return null;
            }
        }

        if (($response->getStatusCode() ?? 0) !== 200) {
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
    }

    /**
     * Throttle durumunda Retry-After header'ı veya exponential backoff'a
     * göre bekleme süresi döndürür.
     */
    private function getThrottleDelay(ClientException $e, int $attempt): int
    {
        $response = $e->getResponse();
        $retryAfter = $response?->getHeaderLine('Retry-After');

        if ($retryAfter !== '') {
            if (ctype_digit($retryAfter)) {
                return min((int) $retryAfter, 60);
            }
            $ts = strtotime($retryAfter);
            if ($ts !== false) {
                return min(max(1, $ts - time()), 60);
            }
        }

        $delay = (int) min(self::INITIAL_BACKOFF * (2 ** ($attempt - 1)), 30);
        $jitter = (int) ($delay * 0.2);
        return $delay + random_int(-$jitter, $jitter);
    }
}
