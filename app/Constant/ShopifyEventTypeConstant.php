<?php

declare(strict_types=1);

namespace App\Constant;

/**
 * shopify_events.type için sabit (generator dışı) event tipleri.
 *
 * Generator'lardan üretilen event'lerin tipi generator handle'ıdır ve burada yer almaz;
 * panelde listelenirken labels() ile birlikte EventGenerator kayıtları da eklenir.
 */
class ShopifyEventTypeConstant
{
    /** Uygulama mağazaya kuruldu (Partner API / app webhook). */
    public const INSTALLED = 'installed';

    /** Uygulama mağazadan kaldırıldı (Partner API). */
    public const UNINSTALLED = 'uninstalled';

    /**
     * @return array<string, string> type => panel etiketi
     */
    public static function labels(): array
    {
        return [
            self::INSTALLED   => 'Kuruldu',
            self::UNINSTALLED => 'Kaldırıldı',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::labels());
    }

    /**
     * Sabit tip mi (generator handle'ı değil)?
     */
    public static function isBuiltIn(string $type): bool
    {
        return array_key_exists($type, self::labels());
    }

    /**
     * Panel etiketi; sabit tip değilse tipin kendisi (generator handle'ı) döner.
     */
    public static function label(string $type): string
    {
        return self::labels()[$type] ?? $type;
    }
}
