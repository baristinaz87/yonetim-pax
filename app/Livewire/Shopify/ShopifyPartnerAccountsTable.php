<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Models\Shopify\PartnerAccount;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Shopify Partner Hesapları')]
class ShopifyPartnerAccountsTable extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search'       => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'sortField'    => ['except' => 'name'],
        'sortDirection'=> ['except' => 'asc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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

    public function toggleActive(int $accountId): void
    {
        $account = PartnerAccount::findOrFail($accountId);
        $account->forceFill(['active' => ! $account->active])->save();

        session()->flash('message', "{$account->name} durumu güncellendi.");
    }

    public function deleteAccount(int $accountId): void
    {
        $account = PartnerAccount::withCount('apps')->findOrFail($accountId);

        if ($account->apps_count > 0) {
            session()->flash('error', "{$account->name} hesabına bağlı {$account->apps_count} uygulama var. Önce uygulamaları başka bir hesaba taşıyın.");
            return;
        }

        $account->delete();
        session()->flash('message', 'Partner hesabı silindi.');
    }

    public function render(): View
    {
        $accounts = PartnerAccount::query()
            ->withCount('apps')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                      ->orWhere('org_id', 'like', '%'.$this->search.'%')
                      ->orWhere('notes', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('active', $this->statusFilter === 'active');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.shopify.shopify-partner-accounts-table', [
            'accounts' => $accounts,
        ]);
    }
}