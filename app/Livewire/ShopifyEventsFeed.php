<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Shopify\Event as ShopifyEvent;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Uygulamalar sayfasının altında gösterilen global event feed.
 * - Tüm shopify_events'i uygulama logosu + mağaza domain'i ile gösterir.
 * - install / uninstall event'ları renklendirilir.
 * - Opsiyonel app filtresi ile tek bir uygulamanın event'larına odaklanılabilir.
 */
#[Layout('components.layouts.app')]
#[Title('Shopify Event Feed')]
class ShopifyEventsFeed extends Component
{
    use WithPagination;

    public string $typeFilter = '';
    public int $appFilter = 0;
    public int $perPage = 15;

    protected $queryString = [
        'typeFilter' => ['except' => ''],
        'appFilter'  => ['except' => 0],
        'page'       => ['except' => 1],
    ];

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAppFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->typeFilter = '';
        $this->appFilter  = 0;
        $this->resetPage();
    }

    public function render(): View
    {
        $events = ShopifyEvent::query()
            ->with([
                'app:id,name,handle,logo',
                'store:id,domain,name',
            ])
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->appFilter > 0, fn ($q) => $q->where('app_id', $this->appFilter))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $apps = \App\Models\Shopify\App::query()
            ->orderBy('name')
            ->get(['id', 'name', 'handle']);

        return view('livewire.shopify-events-feed', [
            'events' => $events,
            'apps'   => $apps,
        ]);
    }
}
