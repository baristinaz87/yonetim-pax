<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class MerchantDetailForm extends Component
{
    private EFaturaClient $eFaturaClient;
    public int $merchantId;
    public array $data = [];
    public array $initialData = [];

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
    }

    public function mount($id): void
    {
        $this->merchantId = $id;
        $this->getData();
    }

    private function getData(): void
    {
        $response = $this->eFaturaClient->getMerchant($this->merchantId);
        $setting = $response['data']['setting'] ?? [];
        if (!empty($setting['last_top_up_at']))
            $setting['last_top_up_at'] = Carbon::parse($setting['last_top_up_at'])->format('Y-m-d');
        if (!empty($setting['credit_tracking_at']))
            $setting['credit_tracking_at'] = Carbon::parse($setting['credit_tracking_at'])->format('Y-m-d');
        if (!empty($setting['credit_expired_at']))
            $setting['credit_expired_at'] = Carbon::parse($setting['credit_expired_at'])->format('Y-m-d');

        $this->data = array_merge($this->data, $setting);
        $this->initialData = $this->data;
    }

    public function updateCreditFields(): void
    {
        $validated = $this->validate([
            "data.credit_expired_at"  => "nullable|string",
            "data.credit_tracking_at" => "nullable|string",
        ]);

        $this->eFaturaClient->updateMerchant($this->merchantId, $validated["data"]);

        session()->flash('updateCreditFieldsMessage', 'Müşteri başarıyla güncellendi.');
        $this->resetExcept('merchantId');
    }

    public function updateSetting(): void
    {
        $validated = $this->validate([
            "data.unvan" => "required|string",
            "data.email" => "required|string",
            "data.phone" => "required|string",
            "data.mobile" => "required|string",
            "data.tax_office" => "required|string",
            "data.tax_number" => "required|string",
            "data.default_tax" => "required|integer",
            "data.tax_override" => "required|boolean",
            "data.confirm" => "required|boolean",
            "data.first_credit" => "required|boolean",
            "data.auto_send" => "required|boolean",
            "data.send_email" => "required|boolean",
            "data.api_user" => "nullable|string",
            "data.api_pass" => "nullable|string",
            "data.xslt_code_efatura" => "nullable|string",
            "data.xslt_code" => "nullable|string",
            "data.status" => "nullable|string",
        ]);

        $this->eFaturaClient->updateMerchant($this->merchantId, $validated["data"]);

        session()->flash('updateSettingMessage', 'Müşteri başarıyla güncellendi.');
        $this->resetExcept('merchantId');
    }

    public function resetForm(): void
    {
        $this->data = $this->initialData;
    }

    public function clearMessageSession($key): void
    {
        session()->remove($key);
    }

    public function render(): View
    {
        $this->getData();
        return view('livewire.merchant-detail-form', $this->data);
    }
}
