<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Models\Shopify\App;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Shopify Uygulamaları')]
class ShopifyAppsTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $partnerFilter = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search'        => ['except' => ''],
        'statusFilter'  => ['except' => ''],
        'partnerFilter' => ['except' => ''],
        'sortField'     => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPartnerFilter(): void
    {
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

    public function toggleActive(int $appId): void
    {
        $app = App::findOrFail($appId);
        $app->forceFill(['active' => ! $app->active])->save();

        session()->flash('message', "{$app->name} durumu güncellendi.");
    }

    public function deleteApp(int $appId): void
    {
        $app = App::findOrFail($appId);

        // İlişkili store_app ve event kayıtlarını da temizle
        $app->stores()->delete();
        $app->events()->delete();
        $app->delete();

        session()->flash('message', "{$app->name} ve ilişkili event kayıtları silindi.");
    }

    public function render(): View
    {
        $apps = App::query()
            ->with('partnerAccount:id,name,org_id')
            ->withCount(['stores', 'activeStores'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                      ->orWhere('handle', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('active', $this->statusFilter === 'active');
            })
            ->when($this->partnerFilter !== '', function ($query) {
                if ($this->partnerFilter === 'none') {
                    $query->whereNull('partner_account_id');
                } else {
                    $query->where('partner_account_id', (int) $this->partnerFilter);
                }
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        $partners = \App\Models\Shopify\PartnerAccount::query()
            ->orderBy('name')
            ->get(['id', 'name', 'org_id']);

        return view('livewire.shopify.shopify-apps-table', [
            'apps'      => $apps,
            'partners'  => $partners,
        ]);
    }
}