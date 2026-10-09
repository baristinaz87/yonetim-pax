<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Client\EFaturaClient;
use App\Constant\ProviderTypeConstant;
use App\Models\EmailContent;
use App\Models\WpContent;
use App\Models\Shopify\EventGenerator;
use App\Models\Shopify\Flow;
use App\Models\Shopify\FlowTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ShopifyFlowTransactions extends Component
{
    use WithPagination;

    public string $status = '';

    public string $targetSearch = '';
    public string $shopSearch = '';
    public string $flowFilter = '';
    public string $eventTypeFilter = '';
    public string $channelFilter = '';

    protected $queryString = [
        'status'          => ['except' => ''],
        'targetSearch'    => ['except' => ''],
        'shopSearch'      => ['except' => ''],
        'flowFilter'      => ['except' => ''],
        'eventTypeFilter' => ['except' => ''],
        'channelFilter'   => ['except' => ''],
    ];

    public int $perPage = 10;

    public array $selected = [];

    public ?int $storeId = null;
    public ?int $appId = null;
    public ?string $storeDomain = null;

    /**
     * true ise kapsamdaki (mağaza/uygulama) hiç kayıt yoksa bileşen hiç render edilmez.
     * Müşteri detay sayfasında kullanılır; filtreler hesaba katılmaz.
     */
    public bool $hideWhenEmpty = false;

    /**
     * @param int|null $id EFatura merchant id. Müşteri detayındaki diğer bileşenlerle
     *                     tutarlı olsun diye `:id` olarak geçilir; mağaza/uygulama
     *                     sayfaları ise `:store-id` / `:app-id` kullanır.
     */
    public function mount(?int $storeId = null, ?int $appId = null, ?int $id = null, bool $hideWhenEmpty = false): void
    {
        $this->storeId = $storeId;
        $this->appId = $appId;
        $this->hideWhenEmpty = $hideWhenEmpty;

        if ($id) {
            $this->storeDomain = $this->resolveMerchantDomain($id);
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'targetSearch', 'shopSearch', 'flowFilter', 'eventTypeFilter', 'channelFilter'], true)) {
            $this->selected = [];
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['status', 'targetSearch', 'shopSearch', 'flowFilter', 'eventTypeFilter', 'channelFilter', 'selected']);
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
        $scopedQuery = FlowTransaction::query()
            ->when(
                $this->storeId,
                fn ($query) => $query->whereHas('event', fn ($query) => $query->where('store_id', $this->storeId))
            )
            ->when(
                $this->storeDomain !== null,
                fn ($query) => $query->whereHas('event.store', fn ($query) => $query->where('domain', $this->storeDomain))
            )
            ->when(
                $this->appId,
                fn ($query) => $query->whereHas('event', fn ($query) => $query->where('app_id', $this->appId))
            );

        if ($this->hideWhenEmpty && !(clone $scopedQuery)->exists()) {
            return view('livewire.shopify.shopify-flow-transactions', ['visible' => false]);
        }

        $transactions = $scopedQuery
            ->with(['flow', 'event.store'])
            ->when(
                $this->status !== '',
                fn ($query) => $query->where('status', $this->status)
            )
            ->when(
                trim($this->targetSearch) !== '',
                fn ($query) => $this->applyTargetSearch($query, trim($this->targetSearch))
            )
            ->when(
                trim($this->shopSearch) !== '',
                fn ($query) => $query->whereHas('event.store', function ($query) {
                    $term = '%'.trim($this->shopSearch).'%';
                    $query->where(fn ($q) => $q->where('domain', 'like', $term)->orWhere('name', 'like', $term));
                })
            )
            ->when(
                $this->flowFilter !== '',
                fn ($query) => $query->where('flow_id', (int) $this->flowFilter)
            )
            ->when(
                $this->eventTypeFilter !== '',
                fn ($query) => $query->whereHas('event', fn ($query) => $query->where('type', $this->eventTypeFilter))
            )
            ->when(
                $this->channelFilter !== '',
                fn ($query) => $query->where('channel', $this->channelFilter)
            )
            ->latest('id')
            ->paginate($this->perPage);

        return view('livewire.shopify.shopify-flow-transactions', [
            'visible' => true,
            'transactions' => $transactions,
            'wpTemplateNamesById' => WpContent::query()
                ->pluck('name', 'brevo_template_id')
                ->all(),
            'emailTemplateNamesById' => EmailContent::query()
                ->pluck('name', 'id')
                ->all(),
            'flows' => Flow::query()->orderBy('name')->pluck('name', 'id')->all(),
            'eventTypes' => [
                'installed' => 'Kuruldu',
                'uninstalled' => 'Kaldırıldı',
                ...EventGenerator::query()->orderBy('name')->pluck('name', 'handle')->all(),
            ],
            'channels' => [
                ProviderTypeConstant::WP_PROVIDER => 'WhatsApp',
                ProviderTypeConstant::EMAIL_PROVIDER => 'E-posta',
                ProviderTypeConstant::SMS_PROVIDER => 'SMS',
            ],
        ]);
    }

    /**
     * EFatura merchant'ının myshopify domain'ini döner. Bulunamazsa boş string döner,
     * böylece tablo başka mağazaların kayıtlarını göstermez.
     */
    private function resolveMerchantDomain(int $merchantId): string
    {
        try {
            $data = (new EFaturaClient())->getMerchant($merchantId)['data'] ?? [];
        } catch (\Throwable) {
            return '';
        }

        return (string) ($data['setting']['shop_myshopify_domain'] ?? $data['name'] ?? '');
    }

    /**
     * Searches the JSON targets column. Phone-like terms are normalized to digits
     * without leading zeros so "0506 623 48 39" matches a stored "905066234839".
     */
    private function applyTargetSearch(Builder $query, string $term): Builder
    {
        if (preg_match('/^[\d\s+\-()]+$/', $term)) {
            $digits = ltrim(preg_replace('/\D/', '', $term), '0');

            if ($digits !== '') {
                $term = $digits;
            }
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->whereRaw('CAST(targets AS CHAR) LIKE ?', ['%'.$escaped.'%']);
    }
}
