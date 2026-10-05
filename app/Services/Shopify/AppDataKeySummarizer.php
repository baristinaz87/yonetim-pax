<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Shopify\StoreAppData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Bir uygulamanın `shopify_store_app_data.data` JSON'larında geçen alan yollarını
 * (dot-notation), tiplerini, doluluk oranlarını ve örnek değerlerini özetler.
 * Event oluşturucu formunda koşul yazarken hangi alanların kullanılabileceğini göstermek için.
 */
class AppDataKeySummarizer
{
    /** İncelenecek en fazla kayıt (en son güncellenenler). */
    private const SAMPLE_RECORDS = 500;

    /** Mağaza id'si gibi dinamik anahtarlı nesneler listeyi şişirmesin diye üst sınır. */
    private const MAX_KEYS = 200;

    private const MAX_SAMPLES = 3;

    /** App data 3 saatte bir güncellendiği için kısa süreli önbellek yeterli. */
    private const CACHE_SECONDS = 600;

    /**
     * @return array{records: int, keys: array<string, array{types: list<string>, count: int, samples: list<string>}>}
     */
    public function summarize(int $appId): array
    {
        return Cache::remember("shopify-app-data-keys:{$appId}", self::CACHE_SECONDS, function () use ($appId): array {
            $rows = StoreAppData::query()
                ->where('app_id', $appId)
                ->latest('updated_at')
                ->limit(self::SAMPLE_RECORDS)
                ->pluck('data');

            $keys = [];
            foreach ($rows as $data) {
                if (is_array($data) && ! array_is_list($data)) {
                    $this->collect($data, '', $keys);
                }
            }

            // En yaygın alanları tut, sonra iç içe alanlar yan yana dursun diye alfabetik sırala.
            uasort($keys, fn (array $a, array $b) => $b['count'] <=> $a['count']);
            $keys = array_slice($keys, 0, self::MAX_KEYS, true);
            ksort($keys, SORT_NATURAL);

            return ['records' => $rows->count(), 'keys' => $keys];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array{types: list<string>, count: int, samples: list<string>}>  $keys
     */
    private function collect(array $data, string $prefix, array &$keys): void
    {
        foreach ($data as $key => $value) {
            $path = $prefix.$key;

            // Nesnelerin içine in; listeler ve skaler değerler koşulda kullanılacak yapraklardır.
            if (is_array($value) && $value !== [] && ! array_is_list($value)) {
                $this->collect($value, $path.'.', $keys);
                continue;
            }

            $keys[$path] ??= ['types' => [], 'count' => 0, 'samples' => []];
            $keys[$path]['count']++;

            $type = $this->typeOf($value);
            if (! in_array($type, $keys[$path]['types'], true)) {
                $keys[$path]['types'][] = $type;
            }

            $sample = $this->sampleOf($value);
            if (count($keys[$path]['samples']) < self::MAX_SAMPLES && ! in_array($sample, $keys[$path]['samples'], true)) {
                $keys[$path]['samples'][] = $sample;
            }
        }
    }

    private function typeOf(mixed $value): string
    {
        return match (true) {
            $value === null => 'boş',
            is_bool($value) => 'bool',
            is_numeric($value) => 'sayı',
            is_array($value) => 'liste',
            preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $value) === 1 => 'tarih',
            default => 'metin',
        };
    }

    private function sampleOf(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_array($value) => '['.count($value).' öğe]',
            default => Str::limit((string) $value, 40),
        };
    }
}
