<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Models\EmailContent;
use App\Models\WpContent;
use App\Models\Shopify\FlowTransaction;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ShopifyFlowTransactions extends Component
{
    use WithPagination;

    public string $status = '';

    public int $perPage = 10;

    public array $selected = [];

    public ?int $storeId = null;
    public ?int $appId = null;

    public function mount(?int $storeId = null, ?int $appId = null): void
    {
        $this->storeId = $storeId;
        $this->appId = $appId;
    }

    public function updatedStatus(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    public function retrySelected(): void
    {
        if (empty($this->selected)) return;

        $transactions = FlowTransaction::query()
            ->whereIn('id', $this->selected)
            ->where('status', FlowTransaction::STATUS_FAILED)
            ->get();

        foreach ($transactions as $transaction) {
            $transaction->tryAgain();
        }

        $this->selected = [];
    }

    public function render(): View
    {
        $transactions = FlowTransaction::query()
            ->with(['flow', 'event.store'])
            ->when(
                $this->storeId,
                fn ($query) => $query->whereHas('event', fn ($query) => $query->where('store_id', $this->storeId))
            )
            ->when(
                $this->appId,
                fn ($query) => $query->whereHas('event', fn ($query) => $query->where('app_id', $this->appId))
            )
            ->when(
                $this->status !== '',
                fn ($query) => $query->where('status', $this->status)
            )
            ->latest('id')
            ->paginate($this->perPage);

        return view('livewire.shopify.shopify-flow-transactions', [
            'transactions' => $transactions,
            'wpTemplateNamesById' => WpContent::query()
                ->pluck('name', 'brevo_template_id')
                ->all(),
            'emailTemplateNamesById' => EmailContent::query()
                ->pluck('name', 'id')
                ->all(),
        ]);
    }
}
