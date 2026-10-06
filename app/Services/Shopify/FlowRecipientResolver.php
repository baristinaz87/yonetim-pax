<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Constant\ProviderTypeConstant;
use App\Models\Shopify\Event;
use App\Models\Shopify\StoreAppData;

/**
 * Bir event için kanal bazında gönderilecek alıcıları (telefon / e-posta) toplar.
 *
 * Kaynaklar: shopify_stores (shop.json) + shopify_store_app_data (uygulamanın verisi).
 * FlowTransactionCreator alıcı yoksa transaction açmamak için, FlowTransactionJob
 * gönderim anındaki güncel alıcılar için kullanır.
 */
class FlowRecipientResolver
{
    /**
     * @return array<int, string>
     */
    public function forChannel(Event $event, string $channel): array
    {
        return match ($channel) {
            ProviderTypeConstant::WP_PROVIDER    => $this->phones($event),
            ProviderTypeConstant::EMAIL_PROVIDER => $this->emails($event),
            default                              => [],
        };
    }

    /**
     * @return array<int, string>
     */
    public function phones(Event $event): array
    {
        return collect(array_merge(
                [$event->store?->phone],
                $this->storeAppDataValues($event, ['phone', 'mobile', 'authorized_phone']),
            ))
            ->filter(fn (mixed $phone) => is_scalar($phone))
            ->map(fn (mixed $phone) => $this->normalizePhone($phone))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function emails(Event $event): array
    {
        return collect(array_merge(
                [$event->store?->email, $event->store?->contact_email],
                $this->storeAppDataValues($event, ['email', 'authorized_email']),
            ))
            ->filter(fn (mixed $email) => is_scalar($email))
            ->map(fn (mixed $email) => strtolower(trim((string) $email)))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $keys
     * @return array<int, mixed>
     */
    private function storeAppDataValues(Event $event, array $keys): array
    {
        if (!$event->store_id || !$event->app_id) return [];

        $data = StoreAppData::query()
            ->where('store_id', $event->store_id)
            ->where('app_id', $event->app_id)
            ->value('data');

        if (!is_array($data)) return [];

        return $this->valuesForKeys($data, $keys);
    }

    /**
     * @param array<mixed> $data
     * @param array<int, string> $keys
     * @return array<int, mixed>
     */
    private function valuesForKeys(array $data, array $keys): array
    {
        $values = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $keys, true)) {
                $values = array_merge($values, is_array($value) ? $value : [$value]);
            }

            if (is_array($value)) {
                $values = array_merge($values, $this->valuesForKeys($value, $keys));
            }
        }

        return $values;
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '90'.substr($digits, 1);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            $digits = '90'.$digits;
        }

        return $digits !== '' ? $digits : null;
    }
}
