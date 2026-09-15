<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Shopify\Event;
use App\Models\Shopify\EventGenerator;
use App\Models\Shopify\StoreAppData;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\JoinClause;
use Throwable;

class EventGeneratorEvaluator
{
    private const MISSING = '__shopify_event_generator_missing__';

    /**
     * @return array{checked: int, matched: int, emitted: int, cooldown_skipped: int, schedule_skipped: int, stale_skipped: int}
     */
    public function evaluate(?int $generatorId = null, bool $dryRun = false): array
    {
        $stats = [
            'checked' => 0,
            'matched' => 0,
            'emitted' => 0,
            'cooldown_skipped' => 0,
            'schedule_skipped' => 0,
            'stale_skipped' => 0,
        ];

        $generators = EventGenerator::query()
            ->active()
            ->when($generatorId, fn ($query) => $query->whereKey($generatorId))
            ->orderBy('id')
            ->get();

        foreach ($generators as $generator) {
            $appIds = array_values(array_filter(array_map('intval', $generator->app_ids ?? [])));
            if ($appIds === []) {
                continue;
            }

            StoreAppData::query()
                ->select('shopify_store_app_data.*')
                ->join('shopify_store_apps', function (JoinClause $join): void {
                    $join->on('shopify_store_apps.store_id', '=', 'shopify_store_app_data.store_id')
                        ->on('shopify_store_apps.app_id', '=', 'shopify_store_app_data.app_id');
                })
                ->where('shopify_store_apps.status', 'active')
                ->whereIn('shopify_store_app_data.app_id', $appIds)
                ->orderBy('shopify_store_app_data.id')
                ->chunkById(200, function ($records) use ($generator, $dryRun, &$stats): void {
                    // Record burda app_data satırı
                    foreach ($records as $record) {
                        $stats['checked']++;

                        // app_data verisi "Veri Tazeliği" süresinden eskiyse
                        if ($generator->max_data_age_minutes !== null
                            && $record->updated_at?->lessThan(now()->subMinutes($generator->max_data_age_minutes))) {
                            $stats['stale_skipped']++;
                            continue;
                        }

                        // Generator için ayarlanmış zaman aralığında değilsek
                        if (! $this->isWithinSchedule($generator->schedule ?? [])) {
                            $stats['schedule_skipped']++;
                            continue;
                        }

                        // Koşullara göre eşleşme kontrolü
                        $matchedValues = $this->matchedValues($record->data ?? [], $generator->conditions ?? [], $generator->condition_logic);
                        if ($matchedValues === null) {
                            continue;
                        }

                        $stats['matched']++;

                        // Tekrar oluşturma için Cooldown süresi kontrolü
                        if ($this->isInCooldown($generator, $record->store_id, $record->app_id)) {
                            $stats['cooldown_skipped']++;
                            continue;
                        }

                        // Önizleme modu kontrolü
                        if ($dryRun) {
                            $stats['emitted']++;
                            continue;
                        }

                        Event::create([
                            'store_id' => $record->store_id,
                            'app_id' => $record->app_id,
                            'event_generator_id' => $generator->id,
                            'type' => $generator->handle,
                            'label' => $generator->name,
                            'data' => [
                                'source' => 'event_generator',
                                'generator' => [
                                    'id' => $generator->id,
                                    'handle' => $generator->handle,
                                    'name' => $generator->name,
                                ],
                                'matched_values' => $matchedValues,
                                'evaluated_at' => now()->toIso8601String(),
                            ],
                            'created_at' => now(),
                        ]);

                        $stats['emitted']++;
                    }
                }, 'shopify_store_app_data.id', 'id');
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $conditions
     * @return array<int, array<string, mixed>>|null
     */
    private function matchedValues(array $data, array $conditions, string $logic): ?array
    {
        if ($conditions === []) {
            return null;
        }

        $matches = [];
        $values = [];

        foreach ($conditions as $condition) {
            $path = (string) ($condition['path'] ?? '');
            $value = data_get($data, $path, self::MISSING);
            $matched = $this->matchesCondition($value, $condition);
            $matches[] = $matched;
            $values[] = [
                'path' => $path,
                'actual' => $value === self::MISSING ? null : $value,
                'operator' => $condition['operator'] ?? null,
                'expected' => $condition['value'] ?? null,
                'value_type' => $condition['value_type'] ?? null,
                'matched' => $matched,
            ];
        }

        // any => en az 1 true varsa | all => Hepsi true ise
        $isMatch = $logic === 'any'
            ? in_array(true, $matches, true)
            : !in_array(false, $matches, true);

        return $isMatch ? $values : null;
    }

    /** @param array<string, mixed> $condition */
    private function matchesCondition(mixed $actual, array $condition): bool
    {
        $operator = (string) ($condition['operator'] ?? 'equals');
        $isMissing = $actual === self::MISSING;

        if ($isMissing) {
            return false;
        }

        $type = (string) ($condition['value_type'] ?? 'number');
        $expected = $condition['value'] ?? null;

        return match ($type) {
            'number' => $this->matchesNumberCondition($actual, $expected, $operator),
            'date' => $this->matchesDateCondition($actual, $condition, $operator),
            default => false,
        };
    }

    private function matchesNumberCondition(mixed $actual, mixed $expected, string $operator): bool
    {
        if (!is_numeric($actual) || !is_numeric($expected)) {
            return false;
        }

        $actual = (float) $actual;
        $expected = (float) $expected;

        return match ($operator) {
            'equals' => $actual === $expected,
            'not_equals' => $actual !== $expected,
            'gt' => $actual > $expected,
            'gte' => $actual >= $expected,
            'lt' => $actual < $expected,
            'lte' => $actual <= $expected,
            default => false,
        };
    }

    /** @param array<string, mixed> $condition */
    private function matchesDateCondition(mixed $actual, array $condition, string $operator): bool
    {
        try {
            $actual = CarbonImmutable::parse((string) $actual, 'Europe/Istanbul')->startOfMinute();
            $valueMode = (string) ($condition['value_mode'] ?? 'fixed');

            switch ($valueMode) {
                case 'fixed':
                    $expected = CarbonImmutable::parse((string) ($condition['value'] ?? ''), 'Europe/Istanbul')->startOfMinute();
                    break;
                case 'dynamic':
                    $days = filter_var($condition['value'] ?? null, FILTER_VALIDATE_INT);
                    if ($days === false) {
                        return false;
                    }
                    $expected = CarbonImmutable::now('Europe/Istanbul')->addDays($days)->startOfMinute();
                    break;
                default:
                    return false;
            }
        } catch (Throwable) {
            return false;
        }

        return match ($operator) {
            'equals' => $actual->equalTo($expected),
            'not_equals' => ! $actual->equalTo($expected),
            'gt' => $actual->greaterThan($expected),
            'gte' => $actual->greaterThanOrEqualTo($expected),
            'lt' => $actual->lessThan($expected),
            'lte' => $actual->lessThanOrEqualTo($expected),
            default => false,
        };
    }

    private function isInCooldown(EventGenerator $generator, int $storeId, int $appId): bool
    {
        if ($generator->cooldown_minutes === 0) {
            return false;
        }

        $lastCreatedAt = Event::query()
            ->where('event_generator_id', $generator->id)
            ->where('store_id', $storeId)
            ->where('app_id', $appId)
            ->latest('created_at')
            ->value('created_at');

        return $lastCreatedAt !== null
            && CarbonImmutable::parse($lastCreatedAt)->greaterThan(now()->subMinutes($generator->cooldown_minutes));
    }

    /** @param array<string, mixed> $schedule */
    private function isWithinSchedule(array $schedule): bool
    {
        $now = CarbonImmutable::now('Europe/Istanbul');
        $start = $schedule['start_time'] ?? null;
        $end = $schedule['end_time'] ?? null;
        $startAt = $start ? $now->setTimeFromTimeString((string) $start) : null;
        // Saat alanı dakika hassasiyetinde; bitiş dakikasının tamamını kapsa.
        $endAt = $end ? $now->setTimeFromTimeString($end.':59') : null;
        $isOvernight = $startAt && $endAt && $startAt->greaterThan($endAt);

        $days = array_map('intval', $schedule['days'] ?? []);
        $scheduleDay = $isOvernight && $now->lessThanOrEqualTo($endAt)
            ? $now->subDay()->dayOfWeekIso
            : $now->dayOfWeekIso;
        if ($days !== [] && !in_array($scheduleDay, $days, true)) {
            return false;
        }

        if (!$start || !$end) {
            return true;
        }

        if ($startAt->lessThanOrEqualTo($endAt)) {
            return $now->betweenIncluded($startAt, $endAt);
        }

        return $now->greaterThanOrEqualTo($startAt) || $now->lessThanOrEqualTo($endAt);
    }
}
