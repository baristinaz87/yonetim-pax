<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Illuminate\View\View;
use Livewire\Component;

class MerchantChargesTable extends Component
{
    protected $listeners = ['credit-added' => '$refresh'];

    public string $merchantId;

    private EFaturaClient $eFaturaClient;

    public int $page = 1;
    public int $perPage = 10;
    public int $total = 10;

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
    }

    public function mount($id): void
    {
        $this->merchantId = $id;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function render(): View
    {
        $data = $this->eFaturaClient->getMerchantCharges(
            $this->merchantId,
            $this->page,
            $this->perPage
        );

        $data["total_records"] = $data["total"];
        return view('livewire.merchant-charges-table', $data);
    }
}
