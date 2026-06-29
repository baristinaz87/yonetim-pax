<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Models\Shopify\Store;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tek bir mağazanın detay sayfası:
 *   - Mağaza bilgileri
 *   - Bu mağazanın kurduğu/kaldırdığı uygulamalar (store_apps)
 *   - Mağazaya ait event timeline (shopify_events)
 */
#[Layout('components.layouts.app')]
#[Title('Mağaza Detayı')]
class ShopifyStoreDetail extends Component
{
    use WithPagination;

    public Store $store;

    public string $eventTypeFilter = '';
    public int $appFilter = 0;
    public int $page = 1;
    public int $perPage = 10;

    public function mount(int $storeId): void
    {
        $this->store = Store::findOrFail($storeId);
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function updatingEventTypeFilter(): void
    {
        $this->page = 1;
    }

    public function updatingAppFilter(): void
    {
        $this->page = 1;
    }

    public function clearFilters(): void
    {
        $this->eventTypeFilter = '';
        $this->appFilter = 0;
        $this->page = 1;
    }

    public function render(): View
    {
        // Mağazanın kurulu olduğu uygulamalar
        $installedApps = $this->store->apps()
            ->with('app:id,name,handle,logo')
            ->orderByDesc('installed_at')
            ->get();

        $activeCount  = $installedApps->where('status', 'active')->count();
        $uninstallCount = $installedApps->where('status', '!=', 'active')->count();

        // Event timeline — mağazaya ait tüm event'lar
        $eventsQuery = $this->store->events()
            ->with('app:id,name,handle,logo')
            ->when($this->eventTypeFilter !== '',
                fn ($q) => $q->where('type', $this->eventTypeFilter))
            ->when($this->appFilter > 0,
                fn ($q) => $q->where('app_id', $this->appFilter))
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

        $allApps = \App\Models\Shopify\App::query()
            ->orderBy('name')
            ->get(['id', 'name', 'handle']);

        return view('livewire.shopify.shopify-store-detail', [
            'installedApps' => $installedApps,
            'activeCount'   => $activeCount,
            'uninstallCount'=> $uninstallCount,
            'events'        => $events,
            'totalEvents'   => $totalEvents,
            'lastPage'      => $lastPage,
            'allApps'       => $allApps,
        ]);
    }
}
