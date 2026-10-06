<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Shopify\App;
use App\Models\Shopify\Event;
use App\Models\Shopify\Store;
use App\Models\Shopify\StoreApp;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Kurulum / kaldırma olaylarını Store, StoreApp ve Event tablolarına işler.
 *
 * Aynı olay iki kaynaktan gelebilir:
 *   - webhook : Uygulama, kurulum/kaldırma anında yonetim'e bildirir (saniyeler içinde).
 *   - partner : Partner API sync'i aynı olayı dakikalar, bazen saatler sonra getirir.
 *
 * Her olay için tek bir Event satırı tutulur. İkinci kaynak geldiğinde yeni event
 * açılmaz (→ InstallJob / flow'lar ikinci kez çalışmaz); mevcut event'in
 * data.sources alanına kaynak eklenir:
 *   { "sources": { "webhook": "2026-10-06T10:00:05+00:00", "partner": "2026-10-06T10:00:00+00:00" } }
 *
 * Eşleştirme: aynı store + app + type için MATCH_WINDOW_MINUTES içinde olan ve bu
 * kaynağı henüz içermeyen en yakın event. data.sources alanı olmayan eski event'ler
 * Partner sync'ten geldiği için "partner" kaynaklı sayılır.
 *
 * Kaynaklar olayları sıra dışı getirebildiği için StoreApp durumu yalnızca olay,
 * kayıttaki son durum değişikliğinden yeniyse güncellenir (StoreApp::changedAfter).
 */
class StoreAppEventRecorder
{
    public const SOURCE_WEBHOOK = 'webhook';
    public const SOURCE_PARTNER = 'partner';

    /** Webhook ile Partner API'nin aynı olayı bildirdiği kabul edilen max zaman farkı. */
    private const MATCH_WINDOW_MINUTES = 15;

    private const LOCK_PREFIX = 'shopify:store-app-event:';
    private const LOCK_SECONDS = 30;
    private const LOCK_WAIT_SECONDS = 10;

    /**
     * @param  array{shop_name?: ?string, shop?: array<string, mixed>, access_token?: ?string}  $context
     * @return Event|null Yeni açılan event; olay zaten kayıtlıysa ya da yeniden yetkilendirmeyse null.
     */
    public function recordInstall(App $app, string $domain, CarbonInterface $occurredAt, string $source, array $context = []): ?Event
    {
        $occurredAt = $this->normalize($occurredAt);

        return $this->withLock($app, $domain, function () use ($app, $domain, $occurredAt, $source, $context) {
            $store    = $this->resolveStore($domain, $context);
            $storeApp = $this->findStoreApp($store, $app);
            $token    = $context['access_token'] ?? null;

            $existing = $this->findMatchingEvent($store, $app, 'installed', $occurredAt, $source);
            if ($existing) {
                $this->addSource($existing, $source, $occurredAt);
                $this->storeToken($store, $app, $token);
                return null;
            }

            // Mağaza zaten aktifken gelen webhook bir yeniden yetkilendirmedir
            // (scope güncellemesi, token yenileme) — yeni kurulum event'i açılmaz.
            if ($source === self::SOURCE_WEBHOOK && $storeApp?->status === 'active') {
                $this->storeToken($store, $app, $token);
                return null;
            }

            if (! $storeApp?->changedAfter($occurredAt)) {
                StoreApp::updateOrCreate(
                    ['store_id' => $store->id, 'app_id' => $app->id],
                    ['status' => 'active', 'installed_at' => $occurredAt, 'uninstalled_at' => null],
                );
                $this->storeToken($store, $app, $token);
            }

            return $this->createEvent($store, $app, 'installed', $occurredAt, $source);
        });
    }

    /**
     * @param  array{shop_name?: ?string}  $context
     * @return Event|null Yeni açılan event; olay zaten kayıtlıysa null.
     */
    public function recordUninstall(App $app, string $domain, CarbonInterface $occurredAt, string $source, array $context = []): ?Event
    {
        $occurredAt = $this->normalize($occurredAt);

        return $this->withLock($app, $domain, function () use ($app, $domain, $occurredAt, $source, $context) {
            $store    = $this->resolveStore($domain, $context);
            $storeApp = $this->findStoreApp($store, $app);

            $existing = $this->findMatchingEvent($store, $app, 'uninstalled', $occurredAt, $source);
            if ($existing) {
                $this->addSource($existing, $source, $occurredAt);
                return null;
            }

            if (! $storeApp?->changedAfter($occurredAt)) {
                StoreApp::updateOrCreate(
                    ['store_id' => $store->id, 'app_id' => $app->id],
                    ['status' => 'uninstalled', 'uninstalled_at' => $occurredAt],
                );
            }

            return $this->createEvent($store, $app, 'uninstalled', $occurredAt, $source);
        });
    }

    /**
     * Webhook ve sync aynı mağaza-uygulama için aynı anda çalışırsa ikisi de
     * "eşleşen event yok" görüp mükerrer event açabilir; kilit ile sıraya girerler.
     */
    private function withLock(App $app, string $domain, Closure $callback): ?Event
    {
        return Cache::lock(self::LOCK_PREFIX."{$app->id}:{$domain}", self::LOCK_SECONDS)
            ->block(self::LOCK_WAIT_SECONDS, $callback);
    }

    /**
     * Sorgu bağlamaları ve created_at saat dilimi bilgisi olmadan yazıldığı için
     * tüm zamanlar uygulama saat dilimine çevrilir.
     */
    private function normalize(CarbonInterface $at): Carbon
    {
        return Carbon::createFromTimestamp($at->getTimestamp(), config('app.timezone'));
    }

    /**
     * @param  array{shop_name?: ?string, shop?: array<string, mixed>}  $context
     */
    private function resolveStore(string $domain, array $context): Store
    {
        $shopName = $context['shop_name'] ?? null;
        $store    = Store::firstOrCreate(['domain' => $domain], ['name' => $shopName]);

        if (! empty($context['shop'])) {
            $store->fillFromShopPayload($context['shop'])->save();
        } elseif ($shopName && ! $store->name) {
            $store->name = $shopName;
            $store->save();
        }

        return $store;
    }

    private function findStoreApp(Store $store, App $app): ?StoreApp
    {
        return StoreApp::query()
            ->where('store_id', $store->id)
            ->where('app_id', $app->id)
            ->first();
    }

    /**
     * Aynı olayın bu kaynaktan ya da diğer kaynaktan daha önce kaydedilmiş halini bulur.
     */
    private function findMatchingEvent(Store $store, App $app, string $type, Carbon $occurredAt, string $source): ?Event
    {
        $candidates = Event::query()
            ->where('store_id', $store->id)
            ->where('app_id', $app->id)
            ->where('type', $type)
            ->whereBetween('created_at', [
                $occurredAt->copy()->subMinutes(self::MATCH_WINDOW_MINUTES),
                $occurredAt->copy()->addMinutes(self::MATCH_WINDOW_MINUTES),
            ])
            ->get();

        $best     = null;
        $bestDiff = null;

        foreach ($candidates as $candidate) {
            $sources = $this->sourcesOf($candidate);

            if (isset($sources[$source])) {
                // Aynı kaynak aynı olayı tekrar bildirdi (sync'in occurredAtMin sınırı,
                // webhook yeniden denemesi) → zaten kayıtlı.
                if (Carbon::parse($sources[$source])->getTimestamp() === $occurredAt->getTimestamp()) {
                    return $candidate;
                }
                // Aynı kaynaktan farklı bir olay (ör. kısa sürede kaldır-kur döngüsü).
                continue;
            }

            $diff = abs($candidate->created_at->getTimestamp() - $occurredAt->getTimestamp());
            if ($bestDiff === null || $diff < $bestDiff) {
                $best     = $candidate;
                $bestDiff = $diff;
            }
        }

        return $best;
    }

    /**
     * @return array<string, string> kaynak => olay zamanı (ISO 8601)
     */
    private function sourcesOf(Event $event): array
    {
        return $event->data['sources']
            ?? [self::SOURCE_PARTNER => $event->created_at->toIso8601String()];
    }

    private function addSource(Event $event, string $source, Carbon $occurredAt): void
    {
        $sources = $this->sourcesOf($event);
        if (isset($sources[$source])) {
            return;
        }

        $sources[$source] = $occurredAt->toIso8601String();
        $event->data      = array_merge($event->data ?? [], ['sources' => $sources]);
        $event->save();
    }

    private function storeToken(Store $store, App $app, ?string $token): void
    {
        if (! $token) {
            return;
        }

        StoreApp::query()
            ->where('store_id', $store->id)
            ->where('app_id', $app->id)
            ->where('status', 'active')
            ->update(['access_token' => $token]);
    }

    private function createEvent(Store $store, App $app, string $type, Carbon $occurredAt, string $source): Event
    {
        $label = $type === 'installed' ? 'Uygulama kuruldu' : 'Uygulama kaldırıldı';

        return Event::create([
            'store_id'   => $store->id,
            'app_id'     => $app->id,
            'type'       => $type,
            'label'      => "{$label} ({$app->name})",
            'data'       => ['sources' => [$source => $occurredAt->toIso8601String()]],
            'created_at' => $occurredAt,
        ]);
    }
}
