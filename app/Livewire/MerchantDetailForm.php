<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use Illuminate\View\View;
use Livewire\Component;

class MerchantDetailForm extends Component
{
    private EFaturaClient $eFaturaClient;
    public int $merchantId;

    public ?string $unvan;
    public ?string $email;
    public ?string $phone;
    public ?string $mobile;
    public ?string $tax_office;
    public ?string $tax_number;
    public ?string $default_tax;
    public ?string $tax_override;
    public ?string $confirm;
    public ?string $first_credit;
    public ?string $api_user;
    public ?string $api_pass;
    public ?string $xslt_code_efatura;
    public ?string $xslt_code;
    public ?string $auto_send;
    public ?string $send_email;
    public ?string $status;

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
    }

    public function mount($id): void
    {
        $this->merchantId = $id;
    }

    public function updateSetting(): void
    {
        $validated = $this->validate([
            "unvan" => "nullable|string",
            "email" => "nullable|string",
            "phone" => "nullable|string",
            "mobile" => "nullable|string",
            "tax_office" => "nullable|string",
            "tax_number" => "nullable|string",
            "default_tax" => "nullable|string",
            "tax_override" => "nullable|string",
            "confirm" => "nullable|string",
            "first_credit" => "nullable|string",
            "api_user" => "nullable|string",
            "api_pass" => "nullable|string",
            "xslt_code_efatura" => "nullable|string",
            "xslt_code" => "nullable|string",
            "auto_send" => "nullable|string",
            "send_email" => "nullable|string",
            "status" => "nullable|string",
        ]);

        $this->eFaturaClient->updateMerchant($this->merchantId, $validated);

        session()->flash('message', 'Merchant başarıyla kaydedildi.');
        $this->resetExcept('merchantId');
    }

    public function render(): View
    {
        $data = $this->eFaturaClient->getMerchant($this->merchantId);

        $this->unvan = $data["data"]["setting"]["unvan"];
        $this->email = $data["data"]["setting"]["email"];
        $this->phone = $data["data"]["setting"]["phone"];
        $this->mobile = $data["data"]["setting"]["mobile"];
        $this->tax_office = $data["data"]["setting"]["tax_office"];
        $this->tax_number = $data["data"]["setting"]["tax_number"];
        $this->default_tax = $data["data"]["setting"]["default_tax"];
        $this->tax_override = $data["data"]["setting"]["tax_override"];
        $this->confirm = $data["data"]["setting"]["confirm"];
        $this->first_credit = $data["data"]["setting"]["first_credit"];
        $this->api_user = $data["data"]["setting"]["api_user"];
        $this->api_pass = $data["data"]["setting"]["api_pass"];
        $this->xslt_code_efatura = $data["data"]["setting"]["xslt_code_efatura"];
        $this->xslt_code = $data["data"]["setting"]["xslt_code"];
        $this->auto_send = $data["data"]["setting"]["auto_send"];
        $this->send_email = $data["data"]["setting"]["send_email"];
        $this->status = $data["data"]["setting"]["status"];

        return view('livewire.merchant-detail-form', $data);
    }
}
