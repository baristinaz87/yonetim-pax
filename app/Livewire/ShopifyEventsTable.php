<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Client\EFaturaClient;
use App\Models\Shopify\Event as ShopifyEvent;
use App\Models\Shopify\Store;
use App\Models\Shopify\StoreApp;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Merchant detail sayfasının en altında, mağazanın Shopify event'lerini gösterir.
 *
 * Akış:
 *   1. $id (merchant id) ile EFatura API'den shop_myshopify_domain çek
 *      (alternatif: parent'tan :shop-domain parametresi de alabilir)
 *   2. shopify_stores.domain ile eşle, store_id'yi bul
 *   3. shopify_events + shopify_store_apps + shopify_apps JOIN'le
 *   4. Tablo + son 30 günlük bar chart göster
 */
class ShopifyEventsTable extends Component
{
    public int $merchantId = 0;
    public string $shopDomain = '';
    public ?int $shopifyStoreId = null;

    public int $page = 1;
    public int $perPage = 5;

    private EFaturaClient $eFaturaClient;

    public function __construct()
    {
        $this->eFaturaClient = new EFaturaClient();
    }

    public function mount($id = null, ?string $shopDomain = null): void
    {
        // Öncelik: parent'tan gelen shop-domain, yoksa kendimiz EFatura'dan çekeriz
        if ($shopDomain) {
            $this->shopDomain = $shopDomain;
            $this->merchantId = (int) ($id ?? request('id') ?? 0);
            return;
        }

        $this->merchantId = (int) ($id ?? request('id') ?? 0);

        if ($this->merchantId > 0) {
            try {
                $response = $this->eFaturaClient->getMerchant($this->merchantId);
                $this->shopDomain = (string) ($response['data']['setting']['shop_myshopify_domain'] ?? '');
            } catch (\Throwable $e) {
                $this->shopDomain = '';
            }
        }
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function render(): View
    {
        $store = null;
        $apps  = collect();
        $events = collect();
        $totalEvents = 0;
        $lastPage = 1;

        if ($this->shopDomain !== '') {
            $store = Store::where('domain', $this->shopDomain)->first();
            if ($store) {
                $this->shopifyStoreId = $store->id;

                $base = ShopifyEvent::query()
                    ->with('app:id,name,handle')
                    ->where('store_id', $store->id);

                $totalEvents = (clone $base)->count();
                $lastPage    = max(1, (int) ceil($totalEvents / $this->perPage));

                // Geçerli sayfayı clamp et
                if ($this->page > $lastPage) {
                    $this->page = $lastPage;
                }

                $events = $base
                    ->orderByDesc('created_at')
                    ->skip(($this->page - 1) * $this->perPage)
                    ->take($this->perPage)
                    ->get();

                $apps = StoreApp::query()
                    ->with('app:id,name,handle')
                    ->where('store_id', $store->id)
                    ->orderByDesc('installed_at')
                    ->get();
            }
        }

        return view('livewire.shopify-events-table', [
            'store'       => $store,
            'apps'        => $apps,
            'events'      => $events,
            'totalEvents' => $totalEvents,
            'lastPage'    => $lastPage,
        ]);
    }
}
