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
    public bool $multiStoreOnly = false;

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
    }

    #[On('status-filter-changed')]
    public function handleStatusFilterChanged(string $status): void
    {
        $this->selectedStatus = $status;
    }

    #[On('multi-store-filter-changed')]
    public function handleMultiStoreFilterChanged(bool $enabled): void
    {
        $this->multiStoreOnly = $enabled;
    }

    public function resetFilters(): void
    {
        $this->selectedStatus = null;
        $this->unvanSearch = null;
        $this->shopDomainSearch = null;
        $this->sortField = null;
        $this->sortDirection = 'desc';
        $this->multiStoreOnly = false;
        $this->dispatch('multi-store-filter-changed', enabled: false)->to(MerchantsReport::class);
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

        $merchants = $data["data"] ?? [];

        if ($this->multiStoreOnly) {
            // Tüm mağazaları VKN üzerinden grupla, sadece 1'den fazla mağazası
            // olan VKN'leri bırak. Aynı tablo yapısı korunur, sadece içerik
            // filtrelenmiş olur.
            $vknCount = [];
            foreach ($merchants as $m) {
                $vkn = isset($m["setting"]["tax_number"])
                    ? trim((string) $m["setting"]["tax_number"])
                    : "";
                if ($vkn === "") {
                    continue;
                }
                $vknCount[$vkn] = ($vknCount[$vkn] ?? 0) + 1;
            }

            $merchants = array_values(array_filter(
                $merchants,
                function ($m) use ($vknCount) {
                    $vkn = isset($m["setting"]["tax_number"])
                        ? trim((string) $m["setting"]["tax_number"])
                        : "";
                    return $vkn !== "" && ($vknCount[$vkn] ?? 0) > 1;
                }
            ));

            // En çok mağaza en üstte olacak şekilde, VKN'ye göre azalan sırada
            usort($merchants, function ($a, $b) use ($vknCount) {
                $aVkn = isset($a["setting"]["tax_number"])
                    ? trim((string) $a["setting"]["tax_number"])
                    : "";
                $bVkn = isset($b["setting"]["tax_number"])
                    ? trim((string) $b["setting"]["tax_number"])
                    : "";
                return ($vknCount[$bVkn] ?? 0) <=> ($vknCount[$aVkn] ?? 0);
            });

            $data["data"]   = $merchants;
            $data["total"]  = count($merchants);
        }

        $data["total_records"] = $data["total"];
        return view('livewire.merchant-table', $data);
    }
}
