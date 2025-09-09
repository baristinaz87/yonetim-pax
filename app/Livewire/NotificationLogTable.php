<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use App\Models\NotificationLog;
use Illuminate\View\View;
use Livewire\Component;

class NotificationLogTable extends Component
{
    private EFaturaClient $eFaturaClient;
    public int $merchantId;
    public array $data = [];

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
    }

    public function mount($id): void
    {
        $this->merchantId = $id;
    }

    public function render(): View
    {
        $response = $this->eFaturaClient->getMerchant($this->merchantId);
        $myShopifyDomain = null;
        if (isset($response["data"]["name"])) $myShopifyDomain = $response["data"]["name"];

        $this->data = NotificationLog::query()
            ->where("myshopify_domain", "=", $myShopifyDomain)
            ->orderBy("created_at", "DESC")
            ->get()->toArray();

        return view('livewire.notification-log-table');
    }
}
