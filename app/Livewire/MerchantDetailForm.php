<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Illuminate\View\View;
use Livewire\Component;

class MerchantDetailForm extends Component
{
    private EFaturaClient $eFaturaClient;
    public int $merchantId;
    public array $data = [
        "unvan" => null,
        "email" => null,
        "phone" => null,
        "mobile" => null,
        "tax_office" => null,
        "tax_number" => null,
        "default_tax" => null,
        "tax_override" => null,
        "confirm" => null,
        "first_credit" => null,
        "api_user" => null,
        "api_pass" => null,
        "xslt_code_efatura" => null,
        "xslt_code" => null,
        "auto_send" => null,
        "send_email" => null,
        "status" => null,
    ];

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
    }

    public function mount($id): void
    {
        $this->merchantId = $id;
    }

    public function clearMessageSession(): void
    {
        session()->remove("message");
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

        session()->flash('message', 'Müşteri başarıyla kaydedildi.');
        $this->resetExcept('merchantId');
    }

    public function render(): View
    {
        $response = $this->eFaturaClient->getMerchant($this->merchantId);
        $this->data = array_merge($this->data, $response['data']['setting'] ?? []);

        return view('livewire.merchant-detail-form', $this->data);
    }
}
