<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Shopify Partner API GraphQL istemcisi.
 *
 * URL şablonu:
 *   https://partners.shopify.com/{ORG_ID}/api/2026-04/graphql.json
 *
 * Auth: X-Shopify-Access-Token header'ı.
 *
 * Rate limit: 4 istek/saniye (Partner API client başına).
 * - İstekler arası 250 ms minimum bekleme
 * - 429 yanıtında exponential backoff (1s, 2s, 4s, 8s, 16s) + jitter
 * - 5 deneme sonrası exception fırlatır
 */
class PartnerClient
{
    /** Shopify rate limit: 4 req/sec → min 250 ms aralık */
    private const MIN_INTERVAL_MICROSECONDS = 250_000;

    /** 429 durumunda en fazla deneme sayısı */
    private const MAX_RETRIES = 5;

    private Client $http;
    private string $orgId;
    private string $token;
    private string $apiVersion;

    /** Son isteğin mikro-saniye zaman damgası */
    private ?int $lastRequestAt = null;

    public function __construct(?string $orgId = null, ?string $token = null, string $apiVersion = '2026-04')
    {
        $this->orgId      = $orgId ?? (string) config('services.shopify.partner.org_id');
        $this->token      = $token ?? (string) config('services.shopify.partner.token');
        $this->apiVersion = $apiVersion;

        $baseUrl = "https://partners.shopify.com/{$this->orgId}/api/{$this->apiVersion}/graphql.json";

        $this->http = new Client([
            'base_uri' => $baseUrl,
            'headers'  => [
                'Content-Type'           => 'application/json',
                'X-Shopify-Access-Token' => $this->token,
                'Accept'                 => 'application/json',
            ],
            'timeout'         => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * GraphQL sorgusu çalıştır, 'data' döndür.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function query(string $graphql, array $variables = []): array
    {
        $attempt     = 0;
        $maxAttempts = self::MAX_RETRIES + 1;

        while (true) {
            $attempt++;

            // İstekler arası minimum bekleme
            $this->throttle();

            try {
                $response = $this->http->post('', [
                    'json' => [
                        'query'     => $graphql,
                        'variables' => $variables,
                    ],
                ]);
            } catch (ClientException $e) {
                $status = $e->getResponse()?->getStatusCode();

                // 429 → exponential backoff ile yeniden dene
                if ($status === 429 && $attempt < $maxAttempts) {
                    $waitMs = $this->backoffMs($attempt);
                    Log::warning("[partner-api] 429 rate limit, {$attempt}. deneme — {$waitMs}ms bekleniyor");
                    $this->sleep($waitMs);
                    continue;
                }

                // 5xx → kısa backoff ile yeniden dene
                if ($status >= 500 && $attempt < $maxAttempts) {
                    $waitMs = $this->backoffMs($attempt);
                    Log::warning("[partner-api] {$status} sunucu hatası, {$attempt}. deneme — {$waitMs}ms bekleniyor");
                    $this->sleep($waitMs);
                    continue;
                }

                throw new RuntimeException(
                    'Shopify Partner API isteği başarısız: '.$e->getMessage(),
                    0,
                    $e,
                );
            } catch (GuzzleException $e) {
                throw new RuntimeException(
                    'Shopify Partner API isteği başarısız: '.$e->getMessage(),
                    0,
                    $e,
                );
            }

            $body = json_decode((string) $response->getBody(), true);

            if (! is_array($body)) {
                throw new RuntimeException('Shopify Partner API geçersiz yanıt döndü.');
            }

            // 200 OK ama body'sinde GraphQL 429 olabilir
            if (! empty($body['errors'])) {
                $extensions = $body['errors'][0]['extensions'] ?? [];
                $code       = $extensions['code'] ?? null;

                if ($code === '429' && $attempt < $maxAttempts) {
                    $waitMs = $this->backoffMs($attempt);
                    Log::warning("[partner-api] 429 (GraphQL), {$attempt}. deneme — {$waitMs}ms bekleniyor");
                    $this->sleep($waitMs);
                    continue;
                }

                $message = $body['errors'][0]['message'] ?? 'Bilinmeyen GraphQL hatası';
                throw new RuntimeException('Shopify Partner API hata: '.$message);
            }

            return $body['data'] ?? [];
        }
    }

    /**
     * İstekler arası minimum bekleme süresini uygula.
     */
    private function throttle(): void
    {
        if ($this->lastRequestAt === null) {
            $this->lastRequestAt = (int) (microtime(true) * 1_000_000);
            return;
        }

        $now     = (int) (microtime(true) * 1_000_000);
        $elapsed = $now - $this->lastRequestAt;
        $wait    = self::MIN_INTERVAL_MICROSECONDS - $elapsed;

        if ($wait > 0) {
            usleep($wait);
        }

        $this->lastRequestAt = (int) (microtime(true) * 1_000_000);
    }

    /**
     * Exponential backoff: 1s, 2s, 4s, 8s, 16s + jitter (0-250ms).
     */
    private function backoffMs(int $attempt): int
    {
        $base   = 1000 * (2 ** ($attempt - 1));
        $jitter = random_int(0, 250);
        return $base + $jitter;
    }

    private function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
