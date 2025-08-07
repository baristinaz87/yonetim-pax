<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Illuminate\View\View;
use Livewire\Component;

class MerchantTable extends Component
{
    private EFaturaClient $eFaturaClient;

    public array $status = [
        "new"               => "Yeni Müşteriler",
        "active"            => "Aktif Müşteriler",
        "passive"           => "Pasif Müşteriler",
        "on_track"            => "Takipteki Müşteriler",
        "wait_return"        => "Dönüş Beklenenler",
        "wait_activation"    => "Akt. Bekleyenler",
        "wait_deactivation"  => "Deakt. Bekleyenler",
    ];

    public int $page = 1;
    public int $perPage = 10;
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
