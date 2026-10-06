<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\Shopify\App;
use App\Models\Shopify\Event;
use App\Models\Shopify\PartnerAccount;
use App\Models\Shopify\Store;
use App\Models\Shopify\StoreApp;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Partner API'den app install/uninstall event'lerini çekip
 * Store / StoreApp / Event tablolarına işler.
 *
 * Birden fazla Partner hesabını destekler:
 *   - Her App, kendi PartnerAccount'una bağlıdır (shopify_apps.partner_account_id).
 *   - Sync sırasında hesap başına ayrı bir PartnerClient örneği oluşturulur
 *     (rate limit her hesaba ayrı uygulanır).
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
        private readonly AdminClient $admin,
        private readonly StoreAppEventRecorder $recorder,
    ) {}

    /**
     * Tüm aktif uygulamaları, ait oldukları partner hesapları üzerinden senkronize et.
     *
     * Partner hesabı atanmamış uygulamalar loglanır ve atlanır.
     * Her partner hesabı için ayrı bir PartnerClient örneği kullanılır.
     */
    public function syncAllApps(): int
    {
        $apps = App::query()
            ->active()
            ->withGid()
            ->with('partnerAccount')
            ->get();

        // Partner hesabı atanmamış uygulamaları ayır
        $appsWithoutAccount = $apps->filter(fn (App $app) => ! $app->partnerAccount);
        if ($appsWithoutAccount->isNotEmpty()) {
            Log::warning('[sync] partner hesabı atanmamış uygulamalar atlandı: '
                .$appsWithoutAccount->pluck('handle')->implode(', '));
        }

        $grouped = $apps
            ->filter(fn (App $app) => $app->partnerAccount && $app->partnerAccount->active)
            ->groupBy('partner_account_id');

        $total = 0;
        foreach ($grouped as $partnerAccountId => $appsForAccount) {
            /** @var PartnerAccount $account */
            $account = $appsForAccount->first()->partnerAccount;
            $client  = new PartnerClient($account);

            Log::info("[sync] partner={$account->name} (org_id={$account->org_id}) üzerinden "
                .$appsForAccount->count().' uygulama senkronize ediliyor');

            foreach ($appsForAccount as $app) {
                $total += $this->syncApp($app, $client);
            }
        }

        return $total;
    }

    /**
     * Tek bir uygulamayı incremental olarak senkronize et.
     *
     * $client opsiyonel: birden çok app'i aynı partner hesabından çekiyorsan
     * dışarıdan ver, yoksa App'in partner hesabından yeni bir istemci oluşturulur.
     */
    public function syncApp(App $app, ?PartnerClient $client = null): int
    {
        if (! $app->shopify_app_gid) {
            Log::info("[sync] {$app->name}: shopify_app_gid yok, atlandı");
            return 0;
        }

        if (! $app->partnerAccount) {
            Log::warning("[sync] {$app->name}: partner hesabı atanmamış, atlandı");
            return 0;
        }

        $client ??= new PartnerClient($app->partnerAccount);

        $occurredAtMin = $app->last_synced_at?->toIso8601String();
        Log::info("[sync] {$app->name} (partner: {$app->partnerAccount->name}) senkronize ediliyor...");

        $events    = $this->fetchAllEvents($client, $app->shopify_app_gid, $occurredAtMin);
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
    private function fetchAllEvents(PartnerClient $client, string $appGid, ?string $occurredAtMin): array
    {
        $types = ['RELATIONSHIP_INSTALLED', 'RELATIONSHIP_UNINSTALLED'];
        $all   = [];
        $after = null;

        do {
            $data = $client->query(self::EVENTS_QUERY, [
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
     *
     * Webhook ile önceden kaydedilmiş olay yeniden açılmaz; StoreAppEventRecorder
     * mevcut event'i "partner" kaynağıyla işaretler.
     */
    private function applyEvent(App $app, array $event, int &$processed): void
    {
        $shop      = $event['shop'] ?? [];
        $domain    = $shop['myshopifyDomain'] ?? null;
        $eventDate = Carbon::parse($event['occurredAt']);
        $isInstall = $event['type'] === 'RELATIONSHIP_INSTALLED';

        if (! $domain) {
            return;
        }

        // Partner API yalnızca domain + name döndürür.
        // Diğer tüm alanlar (email, phone, shop_owner, contact_email, plan vb.)
        // Admin API shop.json'dan ancak access_token varsa çekilebilir.
        $context = ['shop_name' => $shop['name'] ?? null];

        $created = $isInstall
            ? $this->recorder->recordInstall($app, $domain, $eventDate, StoreAppEventRecorder::SOURCE_PARTNER, $context)
            : $this->recorder->recordUninstall($app, $domain, $eventDate, StoreAppEventRecorder::SOURCE_PARTNER, $context);

        if ($created) {
            $processed++;
        }

        if ($isInstall) {
            $this->enrichStore($app, $domain);
        }
    }

    /**
     * Mağaza detaylarını Admin API'den çekmeyi dene.
     * Token yoksa sessizce atla — webhook ya da InstallJob tamamlar.
     */
    private function enrichStore(App $app, string $domain): void
    {
        $store    = Store::where('domain', $domain)->first();
        $storeApp = $store?->apps()->where('app_id', $app->id)->first();

        if (! $storeApp?->access_token || $storeApp->status !== 'active' || ($store->email && $store->phone)) {
            return;
        }

        try {
            $this->admin->fetchAndUpdateStoreDetails($domain, $storeApp->access_token);
        } catch (\Throwable $e) {
            Log::warning("[sync] {$domain} zenginleştirme atlandı: ".$e->getMessage());
        }
    }
}