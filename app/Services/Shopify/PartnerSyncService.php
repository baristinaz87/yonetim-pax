<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Shopify\App;
use App\Models\Shopify\Event;
use App\Models\Shopify\Store;
use App\Models\Shopify\StoreApp;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Partner API'den app install/uninstall event'lerini çekip
 * Store / StoreApp / Event tablolarına işler.
 *
 * Orijinal Node.js referansı:
 *   server/src/services/partnerSync.js
 */
class PartnerSyncService
{
    private const EVENTS_QUERY = <<<'GRAPHQL'
    query AppEvents($appId: ID!, $types: [AppEventTypes!], $after: String, $occurredAtMin: DateTime) {
      app(id: $appId) {
        id
        name
        events(first: 100, types: $types, after: $after, occurredAtMin: $occurredAtMin) {
          pageInfo { hasNextPage }
          edges {
            cursor
            node {
              type
              occurredAt
              shop {
                myshopifyDomain
                name
              }
            }
          }
        }
      }
    }
    GRAPHQL;

    public function __construct(
        private readonly PartnerClient $partner,
        private readonly AdminClient   $admin,
    ) {}

    /**
     * Tüm aktif uygulamaları senkronize et.
     */
    public function syncAllApps(): void
    {
        $apps = App::query()
            ->active()
            ->withGid()
            ->get();

        foreach ($apps as $app) {
            $this->syncApp($app);
        }
    }

    /**
     * Tek bir uygulamayı incremental olarak senkronize et.
     */
    public function syncApp(App $app): int
    {
        if (! $app->shopify_app_gid) {
            Log::info("[sync] {$app->name}: shopify_app_gid yok, atlandı");
            return 0;
        }

        $occurredAtMin = $app->last_synced_at?->toIso8601String();
        Log::info("[sync] {$app->name} senkronize ediliyor...");

        $events    = $this->fetchAllEvents($app->shopify_app_gid, $occurredAtMin);
        $processed = 0;
        $latest    = $app->last_synced_at;

        foreach ($events as $event) {
            $this->applyEvent($app, $event, $processed);

            $eventDate = Carbon::parse($event['occurredAt']);
            if (! $latest || $eventDate->gt($latest)) {
                $latest = $eventDate;
            }
        }

        if ($latest && (! $app->last_synced_at || $latest->gt($app->last_synced_at))) {
            $app->forceFill(['last_synced_at' => $latest])->save();
        }

        Log::info("[sync] {$app->name}: {$processed} yeni olay işlendi");
        return $processed;
    }

    /**
     * Geçmişi sıfırlayıp tüm event'leri baştan içe aktar.
     */
    public function fullResync(string $handle): void
    {
        $app = App::where('handle', $handle)->firstOrFail();

        DB::transaction(function () use ($app) {
            StoreApp::where('app_id', $app->id)->delete();
            Event::where('app_id', $app->id)->delete();
            $app->forceFill(['last_synced_at' => null])->save();
        });

        $fresh = App::where('handle', $handle)->firstOrFail();
        $this->syncApp($fresh);
    }

    /**
     * Tüm event'leri cursor ile topla, eskiden yeniye sırala.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllEvents(string $appGid, ?string $occurredAtMin): array
    {
        $types = ['RELATIONSHIP_INSTALLED', 'RELATIONSHIP_UNINSTALLED'];
        $all   = [];
        $after = null;

        do {
            $data = $this->partner->query(self::EVENTS_QUERY, [
                'appId'         => $appGid,
                'types'         => $types,
                'after'         => $after,
                'occurredAtMin' => $occurredAtMin,
            ]);

            $edges    = $data['app']['events']['edges'] ?? [];
            $pageInfo = $data['app']['events']['pageInfo'] ?? ['hasNextPage' => false];

            foreach ($edges as $edge) {
                $after = $edge['cursor'];
                $all[] = $edge['node'];
            }
        } while (! empty($pageInfo['hasNextPage']));

        // Eskiden yeniye — son olay StoreApp'in nihai durumunu belirler.
        usort($all, fn ($a, $b) => strcmp((string) $a['occurredAt'], (string) $b['occurredAt']));
        return $all;
    }

    /**
     * Tek bir event'i veritabanına uygula.
     */
    private function applyEvent(App $app, array $event, int &$processed): void
    {
        $type      = $event['type'];
        $shop      = $event['shop'] ?? [];
        $domain    = $shop['myshopifyDomain'] ?? null;
        $shopName  = $shop['name'] ?? null;
        $eventDate = Carbon::parse($event['occurredAt']);
        $isInstall = $type === 'RELATIONSHIP_INSTALLED';

        if (! $domain) {
            return;
        }

        // Partner API yalnızca domain + name döndürür.
        // Diğer tüm alanlar (email, phone, shop_owner, contact_email, plan vb.)
        // Admin API shop.json'dan ancak access_token varsa çekilebilir.
        $store = Store::firstOrCreate(
            ['domain' => $domain],
            ['name'   => $shopName],
        );

        if ($shopName && ! $store->name) {
            $store->name = $shopName;
            $store->save();
        }

        $storeApp = StoreApp::updateOrCreate(
            [
                'store_id' => $store->id,
                'app_id'   => $app->id,
            ],
            $isInstall
                ? [
                    'status'        => 'active',
                    'installed_at'  => $eventDate,
                    'uninstalled_at'=> null,
                ]
                : [
                    'status'        => 'uninstalled',
                    'uninstalled_at'=> $eventDate,
                ],
        );

        // Kurulum event'inde mağaza detaylarını Admin API'den çekmeyi dene.
        // Token yoksa sessizce atla — webhook geldiğinde tekrar denenecek.
        if ($isInstall && $storeApp->access_token && (! $store->email || ! $store->phone)) {
            try {
                $this->admin->fetchAndUpdateStoreDetails($domain, $storeApp->access_token);
            } catch (\Throwable $e) {
                Log::warning("[sync] {$domain} zenginleştirme atlandı: ".$e->getMessage());
            }
        }

        // Duplikasyon kontrolü
        $alreadyExists = Event::query()
            ->where('store_id', $store->id)
            ->where('app_id', $app->id)
            ->where('type', $isInstall ? 'installed' : 'uninstalled')
            ->where('created_at', $eventDate)
            ->exists();

        if (! $alreadyExists) {
            Event::create([
                'store_id'   => $store->id,
                'app_id'     => $app->id,
                'type'       => $isInstall ? 'installed' : 'uninstalled',
                'label'      => ($isInstall ? 'Uygulama kuruldu' : 'Uygulama kaldırıldı')." ({$app->name})",
                'created_at' => $eventDate,
            ]);
            $processed++;
        }
    }
}
