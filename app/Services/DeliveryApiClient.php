<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Shopify\App;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Harici iç API istemcisi — App başına örneklenir.
 *
 * Her Shopify uygulamasının kendine ait API konfigürasyonu olabilir:
 *   - api_auth_endpoint              → /api/login (bearer token almak için)
 *   - get_access_token_endpoint      → /api/get-access-token (shop başına access token)
 *   - auth_email                     → login için kullanıcı adı
 *   - auth_password                  → login için parola
 *
 * HTTP timeout sabit olarak 20 saniyedir (DeliveryApiClient::DEFAULT_TIMEOUT).
 *
 * Token bellekte tutulur ve her çağrıda TTL kontrol edilir.
 * İlk login isteğiyle elde edilen token sonraki isteklerde otomatik yenilenir.
 */
class DeliveryApiClient
{
    /** HTTP timeout — sabit. App'ten okunmaz. */
    public const DEFAULT_TIMEOUT = 20;

    /** 429 rate limit için max retry sayısı */
    private const MAX_RETRIES_429 = 5;

    /** 429 için ilk bekleme (saniye) — sonraki denemelerde exponential backoff */
    private const INITIAL_BACKOFF_429 = 2;

    /** Token ömrü (saniye). Yenileme için eşik değer. */
    private const TOKEN_TTL_SECONDS = 3000; // 50 dk — garanti altında

    private Client $http;

    /** Login sonrası alınan bearer token */
    private ?string $bearerToken = null;

    /** Token alındığı unix timestamp */
    private ?int $tokenAcquiredAt = null;

    public function __construct(private readonly App $app)
    {
        if (! $app->api_auth_endpoint || ! $app->get_access_token_endpoint) {
            throw new RuntimeException(
                "App [{$app->handle}] için API endpoint'leri tanımlı değil. "
                .'Shopify → Uygulamalar sayfasından api_auth_endpoint ve get_access_token_endpoint alanlarını doldurun.'
            );
        }

        // base_uri = auth endpoint'in host kısmı (path'leri kendimiz ekliyoruz)
        $baseUri = $this->buildBaseUri($app->api_auth_endpoint);

        $this->http = new Client([
            'base_uri'        => $baseUri,
            'timeout'         => self::DEFAULT_TIMEOUT,
            'connect_timeout' => 10,
            'headers'         => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * App'in api_auth_endpoint'ine POST atıp bearer token al.
     * Başarı durumunda bellekteki token'ı yeniler ve döner.
     */
    public function login(): string
    {
        if (! $this->app->auth_email || ! $this->app->auth_password) {
            throw new RuntimeException(
                "App [{$this->app->handle}] için auth_email veya auth_password tanımlı değil."
            );
        }

        $loginPath = $this->extractPath($this->app->api_auth_endpoint);

        try {
            $response = $this->http->post($loginPath, [
                'json' => [
                    'email'    => $this->app->auth_email,
                    'password' => $this->app->auth_password,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException(
                "App [{$this->app->handle}] API login başarısız: ".$e->getMessage(),
                0,
                $e,
            );
        }

        $body = json_decode((string) $response->getBody(), true);

        // Yanıt formatı esnek: { token } | { access_token } | { data.token } | { data.access_token }
        $token = $body['token']
            ?? $body['access_token']
            ?? $body['data']['token']
            ?? $body['data']['access_token']
            ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException(
                "App [{$this->app->handle}] login yanıtında token bulunamadı: "
                .json_encode($body, JSON_UNESCAPED_UNICODE)
            );
        }

        $this->bearerToken     = $token;
        $this->tokenAcquiredAt = time();

        Log::info("[delivery-api] login başarılı: app={$this->app->handle}");
        return $token;
    }

    /**
     * Geçerli bearer token'ı döner — gerekirse yeniden login olur.
     */
    public function bearerToken(): string
    {
        if ($this->bearerToken === null
            || $this->tokenAcquiredAt === null
            || (time() - $this->tokenAcquiredAt) >= self::TOKEN_TTL_SECONDS
        ) {
            $this->login();
        }

        return $this->bearerToken;
    }

    /**
     * App'in get_access_token_endpoint'ine shop parametresi ile POST at.
     * Bearer token ile mağazanın Shopify OAuth access token'ını döner.
     *
     * 429 rate limit durumunda Retry-After header'ına veya exponential
     * backoff'a göre bekleyip tekrar dener.
     *
     * @return string|null Token bulunursa string, bulunamazsa null.
     */
    public function getAccessTokenByShop(string $shopDomain): ?string
    {
        $token = $this->bearerToken();

        $tokenPath = $this->extractPath($this->app->get_access_token_endpoint);

        $attempt = 0;
        while (true) {
            try {
                $response = $this->http->post($tokenPath, [
                    'headers' => [
                        'Authorization' => 'Bearer '.$token,
                        'Accept'        => 'application/json',
                    ],
                    'query' => [
                        'shop' => $shopDomain,
                    ],
                ]);
                break; // başarılı — döngüden çık
            } catch (ClientException $e) {
                $status = $e->getResponse()?->getStatusCode();

                // 429 → rate limit. Retry-After header'ı varsa onu kullan,
                // yoksa exponential backoff uygula.
                if ($status === 429) {
                    $attempt++;
                    if ($attempt > self::MAX_RETRIES_429) {
                        throw new RuntimeException(
                            "API get-token 429 rate limit aşıldı ({$attempt} deneme) [app={$this->app->handle}]",
                            0,
                            $e,
                        );
                    }
                    $wait = $this->getRetryAfterDelay($e, $attempt);
                    Log::warning("[delivery-api] {$shopDomain}: 429 rate limit, {$wait}s bekleniyor (deneme {$attempt}/".self::MAX_RETRIES_429.', app='.$this->app->handle.')');
                    sleep($wait);
                    continue;
                }

                // 401/403 → token geçersiz olmuş olabilir, bir kez daha dene
                if ($status === 401 || $status === 403) {
                    Log::warning("[delivery-api] {$shopDomain}: token reddedildi ({$status}), yeniden login deneniyor (app={$this->app->handle})");
                    $this->login();
                    $token = $this->bearerToken();
                    continue;
                }

                // 404 → mağaza bu hesapta yok, sessizce null dön
                if ($status === 404) {
                    Log::info("[delivery-api] {$shopDomain}: bu hesapta tanımlı değil (app={$this->app->handle})");
                    return null;
                }

                throw new RuntimeException(
                    "API get-token başarısız ({$status}) [app={$this->app->handle}]: ".$e->getMessage(),
                    0,
                    $e,
                );
            } catch (GuzzleException $e) {
                throw new RuntimeException(
                    "API get-token başarısız [app={$this->app->handle}]: ".$e->getMessage(),
                    0,
                    $e,
                );
            }
        }

        $body = json_decode((string) $response->getBody(), true);

        // Yanıt formatı esnek:
        //   { password: '...' }  |  { access_token: '...' }
        //   { data: { password: '...', access_token: '...' } }
        $token = $body['password']
            ?? $body['access_token']
            ?? $body['data']['password']
            ?? $body['data']['access_token']
            ?? null;

        if (! is_string($token) || $token === '') {
            Log::info("[delivery-api] {$shopDomain}: yanıtta token yok (app={$this->app->handle}) — ".json_encode($body, JSON_UNESCAPED_UNICODE));
            return null;
        }

        return $token;
    }

    /**
     * 429 için bekleme süresini hesapla.
     * Retry-After header'ı varsa onu tercih et (saniye veya HTTP-tarih),
     * yoksa exponential backoff kullan.
     */
    private function getRetryAfterDelay(ClientException $e, int $attempt): int
    {
        $response = $e->getResponse();
        $retryAfter = $response?->getHeaderLine('Retry-After');

        if ($retryAfter !== '') {
            // Saniye cinsinden olabilir
            if (ctype_digit($retryAfter)) {
                return min((int) $retryAfter, 60); // max 60s
            }
            // HTTP-tarih formatı
            $ts = strtotime($retryAfter);
            if ($ts !== false) {
                return min(max(1, $ts - time()), 60);
            }
        }

        // Exponential backoff: 2, 4, 8, 16, 32 (cap 30)
        $delay = (int) min(self::INITIAL_BACKOFF_429 * (2 ** ($attempt - 1)), 30);

        // ±%20 jitter ekleyerek thundering herd'i önle
        $jitter = (int) ($delay * 0.2);
        return $delay + random_int(-$jitter, $jitter);
    }

    /**
     * Tam URL'den base URI çıkar (scheme + host + port + /).
     */
    private function buildBaseUri(string $url): string
    {
        $parsed = parse_url($url);
        return sprintf(
            '%s://%s%s/',
            $parsed['scheme'] ?? 'https',
            $parsed['host'] ?? '',
            isset($parsed['port']) ? ':'.$parsed['port'] : '',
        );
    }

    /**
     * Tam URL'den path kısmını çıkar (örn: "https://x.com/api/login" → "api/login").
     */
    private function extractPath(string $url): string
    {
        $parsed = parse_url($url);
        $path   = $parsed['path'] ?? '/';
        // başındaki /'yi kaldır (Guzzle'a path olarak veriyoruz)
        return ltrim($path, '/');
    }
}