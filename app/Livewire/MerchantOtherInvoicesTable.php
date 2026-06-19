<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Illuminate\View\View;
use Livewire\Component;

class MerchantOtherInvoicesTable extends Component
{
    public string $merchantId;

    private EFaturaClient $eFaturaClient;

    public int $page = 1;
    public int $perPage = 10;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $selectedSource = null;

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

    public function setSource(?string $source): void
    {
        $this->selectedSource = $source;
        $this->page = 1;
    }

    public function applyDateFilter(): void
    {
        $this->page = 1;
    }

    public function resetFilters(): void
    {
        $this->startDate = null;
        $this->endDate = null;
        $this->selectedSource = null;
        $this->page = 1;
    }

    public function render(): View
    {
        $data = $this->eFaturaClient->getMerchantOtherInvoices(
            $this->merchantId,
            $this->page,
            $this->perPage,
            $this->selectedSource,
            $this->startDate,
            $this->endDate
        );

        $data["total_records"] = $data["total"] ?? 0;
        $data["source_counts"] = [
            "all" => $data["total"] ?? 0,
            "coming" => 0,
            "earchive" => 0,
            "efatura" => 0,
        ];

        $allInvoices = $data["data"] ?? [];
        foreach ($allInvoices as $invoice) {
            $source = $invoice["source"] ?? null;
            if (isset($data["source_counts"][$source])) {
                $data["source_counts"][$source]++;
            }
        }

        return view('livewire.merchant-other-invoices-table', $data);
    }
}
