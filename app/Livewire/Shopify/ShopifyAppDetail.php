<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Models\Shopify\App;
use App\Models\Shopify\Event;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tek bir uygulamanın detay sayfası:
 *   - Uygulama bilgileri
 *   - Bu uygulamanın kurulduğu/kaldırıldığı mağaza listesi (store_apps)
 *   - Mağaza bazlı event timeline (shopify_events)
 */
#[Layout('components.layouts.app')]
#[Title('Uygulama Detayı')]
class ShopifyAppDetail extends Component
{
    use WithPagination;

    public App $app;

    public string $eventTypeFilter = '';
    public int $page = 1;
    public int $perPage = 10;

    public function mount(int $appId): void
    {
        $this->app = App::with('partnerAccount:id,name,org_id')->findOrFail($appId);
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function updatingEventTypeFilter(): void
    {
        $this->page = 1;
    }

    public function render(): View
    {
        $stores = $this->app->stores()
            ->with('store:id,domain,name,email')
            ->addSelect([
                'latest_event_at' => Event::select('created_at')
                    ->whereColumn('store_id', 'shopify_store_apps.store_id')
                    ->whereColumn('app_id', 'shopify_store_apps.app_id')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ])
            // En son event alan mağaza üstte; event yoksa kurulum tarihine göre
            ->orderByRaw('latest_event_at IS NULL, latest_event_at DESC')
            ->orderByDesc('installed_at')
            ->get();

        $activeInstalls  = $stores->where('status', 'active')->count();
        $totalInstalls   = $stores->count();
        $uninstallCount  = $stores->where('status', '!=', 'active')->count();

        // Event'ler — bu uygulamanın tüm event'ları
        $eventsQuery = $this->app->events()
            ->with('store:id,domain,name')
            ->when($this->eventTypeFilter !== '',
                fn ($q) => $q->where('type', $this->eventTypeFilter))
            ->orderByDesc('created_at');

        $totalEvents = $eventsQuery->count();
        $lastPage    = max(1, (int) ceil($totalEvents / $this->perPage));

        if ($this->page > $lastPage) {
            $this->page = $lastPage;
        }

        $events = $eventsQuery
            ->skip(($this->page - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();

        return view('livewire.shopify.shopify-app-detail', [
            'stores'        => $stores,
            'activeInstalls'=> $activeInstalls,
            'totalInstalls' => $totalInstalls,
            'uninstallCount'=> $uninstallCount,
            'events'        => $events,
            'totalEvents'   => $totalEvents,
            'lastPage'      => $lastPage,
        ]);
    }
}
