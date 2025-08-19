<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Illuminate\View\View;
use Livewire\Component;

class MerchantsReport extends Component
{
    private EFaturaClient $eFaturaClient;

    public array $data = [];

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
    }

    public function selectStatusFilter($status): void
    {
        $this->dispatch("status-filter-changed", status: $status);
    }

    public function render(): View
    {
        $this->data = $this->eFaturaClient->getMerchantStatusReport();
        return view('livewire.merchants-report');
    }
}
