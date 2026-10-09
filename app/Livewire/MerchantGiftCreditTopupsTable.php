<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Müşteri detayında hediye kontör yükleme geçmişi (gift_credit_topup_history) tablosu.
 * Mağazaya ait hiç kayıt yoksa bölüm hiç gösterilmez.
 */
class MerchantGiftCreditTopupsTable extends Component
{
    public string $merchantId;

    private EFaturaClient $eFaturaClient;

    public int $page = 1;
    public int $perPage = 10;

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
        $data = $this->eFaturaClient->getMerchantGiftCreditTopups(
            $this->merchantId,
            $this->page,
            $this->perPage
        );

        $data["data"] = $data["data"] ?? [];
        $data["total_records"] = $data["total"] ?? 0;
        return view('livewire.merchant-gift-credit-topups-table', $data);
    }
}
