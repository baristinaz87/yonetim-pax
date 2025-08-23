<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class MerchantTable extends Component
{
    private EFaturaClient $eFaturaClient;

    public int $page = 1;
    public int $perPage = 100000;
    public int $total = 10;
    public ?string $sortField = null;
    public string $sortDirection = "desc";
    public ?string $unvanSearch = null;
    public ?string $shopDomainSearch = null;
    public ?string $selectedStatus = null;

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
    }

    #[On('status-filter-changed')]
    public function handleStatusFilterChanged(string $status): void
    {
        $this->selectedStatus = $status;
    }

    public function resetFilters(): void
    {
        $this->selectedStatus = null;
        $this->unvanSearch = null;
        $this->shopDomainSearch = null;
        $this->sortField = null;
        $this->sortDirection = 'desc';
    }

    public function setSort(string $sortField): void
    {
        $this->sortField = $sortField;
        $this->sortDirection = $this->sortDirection === "asc" ? "desc" : "asc";
        $this->page = 1;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function render(): View
    {
        $data = $this->eFaturaClient->getMerchants(
            $this->page,
            $this->perPage,
            $this->sortField,
            $this->sortDirection,
            [
                "setting.unvan" => $this->unvanSearch,
                "setting.shop_domain" => $this->shopDomainSearch,
                "setting.status" => $this->selectedStatus,
            ]
        );

        $data["total_records"] = $data["total"];
        return view('livewire.merchant-table', $data);
    }
}
