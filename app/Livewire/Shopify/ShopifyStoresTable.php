<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Models\Shopify\App;
use App\Models\Shopify\Store;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Mağazalar (Stores) listesi.
 * - Tüm shopify_stores tablosunu sayfalar.
 * - Uygulama bazlı filtre (yalnızca o uygulamayı kurmuş mağazalar).
 * - Arama (domain / name / email).
 */
#[Layout('components.layouts.app')]
#[Title('Shopify Mağazaları')]
class ShopifyStoresTable extends Component
{
    use WithPagination;

    public string $search = '';
    public int $appFilter = 0;
    public string $statusFilter = '';
    public string $sortField = 'domain';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search'       => ['except' => ''],
        'appFilter'    => ['except' => 0],
        'statusFilter' => ['except' => ''],
        'sortField'    => ['except' => 'domain'],
        'sortDirection'=> ['except' => 'asc'],
        'page'         => ['except' => 1],
    ];

    public function mount(): void
    {
        // ?app_id=… ile deep-link gelirse filtre olarak uygula
        if (request()->has('app_id')) {
            $this->appFilter = (int) request('app_id');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingAppFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearAppFilter(): void
    {
        $this->appFilter = 0;
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
        $this->resetPage();
    }

    public function render(): View
    {
        $stores = Store::query()
            ->withCount([
                'apps',
                'activeApps',
                'events',
            ])
            ->with(['apps' => function ($q) {
                $q->with('app:id,name,handle,logo')->orderByDesc('installed_at');
            }])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('domain', 'like', '%'.$this->search.'%')
                      ->orWhere('name', 'like', '%'.$this->search.'%')
                      ->orWhere('email', 'like', '%'.$this->search.'%')
                      ->orWhere('shop_owner', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->appFilter > 0, function ($query) {
                // Yalnızca seçili uygulamayı kurmuş mağazalar
                $query->whereHas('apps', fn ($q) => $q->where('app_id', $this->appFilter));
            })
            ->when($this->statusFilter !== '', function ($query) {
                if ($this->statusFilter === 'has_active') {
                    $query->has('activeApps');
                } elseif ($this->statusFilter === 'no_active') {
                    $query->doesntHave('activeApps');
                }
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        $apps = App::query()
            ->orderBy('name')
            ->get(['id', 'name', 'handle']);

        $filteringApp = $this->appFilter > 0
            ? $apps->firstWhere('id', $this->appFilter)
            : null;

        return view('livewire.shopify.shopify-stores-table', [
            'stores'      => $stores,
            'apps'        => $apps,
            'filteringApp'=> $filteringApp,
        ]);
    }
}
