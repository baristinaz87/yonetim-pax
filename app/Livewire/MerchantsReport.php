<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class MerchantsReport extends Component
{
    private EFaturaClient $eFaturaClient;

    public array $data = [];
    public array $multiStoreData = [];
    public bool $multiStoreEnabled = false;

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
    }

    public function selectStatusFilter($status): void
    {
        $this->dispatch("status-filter-changed", status: $status);
    }

    public function toggleMultiStoreFilter(): void
    {
        $this->multiStoreEnabled = !$this->multiStoreEnabled;
        $this->dispatch(
            "multi-store-filter-changed",
            enabled: $this->multiStoreEnabled
        )->to(MerchantTable::class);
    }

    #[On('multi-store-filter-changed')]
    public function syncMultiStoreEnabled(bool $enabled): void
    {
        $this->multiStoreEnabled = $enabled;
    }

    public function render(): View
    {
        $this->data = $this->eFaturaClient->getMerchantStatusReport();
        $this->multiStoreData = $this->eFaturaClient->getMultiStoreGroups();
        return view('livewire.merchants-report');
    }
}
