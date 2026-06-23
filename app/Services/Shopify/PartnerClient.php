<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Shopify\PartnerAccount;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Shopify Partner API GraphQL istemcisi.
 *
 * URL şablonu:
 *   https://partners.shopify.com/{ORG_ID}/api/{API_VERSION}/graphql.json
 *
 * Auth: X-Shopify-Access-Token header'ı.
 *
 * Her partner hesabı için ayrı bir istemci örneği oluşturulmalıdır —
 * rate limit (4 req/sec) hesap başına uygulanır.
 *
 * Rate limit davranışı:
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
    private ?int $lastRequestAt = null;

    /**
     * PartnerClient oluştur.
     *
     * Öncelik sırası:
     *   1. Geçirilen PartnerAccount (önerilen)
     *   2. Geçirilen ham değerler (test/iç kullanım)
     *   3. Veritabanındaki ilk aktif partner hesabı (geriye uyumluluk)
     *   4. .env fallback (geçiş dönemi)
     */
    public function __construct(
        ?PartnerAccount $account = null,
        ?string $orgId = null,
        ?string $token = null,
        ?string $apiVersion = null,
    ) {
        if ($account) {
            $this->orgId      = $account->org_id;
            $this->token      = $account->access_token;
            $this->apiVersion = $account->api_version ?: '2026-04';
        } else {
            $fallback = $this->resolveFallbackAccount();

            if ($fallback) {
                $this->orgId      = $fallback->org_id;
                $this->token      = $fallback->access_token;
                $this->apiVersion = $fallback->api_version ?: '2026-04';
            } else {
                $this->orgId      = (string) ($orgId ?? config('services.shopify.partner.org_id'));
                $this->token      = (string) ($token ?? config('services.shopify.partner.token'));
                $this->apiVersion = (string) ($apiVersion ?? config('services.shopify.partner.api_version', '2026-04'));
            }
        }

        if ($this->orgId === '' || $this->token === '') {
            throw new RuntimeException(
                'Shopify PartnerClient için org_id ve access_token gerekli. '
                .'Lütfen shopify_partner_accounts tablosuna bir kayıt ekleyin veya .env üzerinden geçici olarak tanımlayın.'
            );
        }

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
     * Geriye uyumluluk: parametre gelmemişse veritabanındaki ilk aktif partner hesabını dön.
     * Bu, eski tek-partner kurulumlarında komutların çalışmaya devam etmesini sağlar.
     */
    private function resolveFallbackAccount(): ?PartnerAccount
    {
        try {
            return PartnerAccount::query()->active()->orderBy('id')->first();
        } catch (\Throwable) {
            // Tablo henüz migrate edilmediyse sessizce geç — eski davranışa düş.
            return null;
        }
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