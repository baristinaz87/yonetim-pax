<?php

namespace App\Livewire;

use App\Constant\AppTypeConstant;
use App\Constant\ProviderTypeConstant;
use App\Models\EmailContent;
use App\Models\NotificationLog;
use App\Services\BrevoService;
use Livewire\Component;

class EmailMessageModal extends Component
{
    private BrevoService $brevoService;
    public array $formData = [];
    public array $templates = [];

    public function __construct()
    {
        $this->brevoService = new BrevoService();
        $emailContents = EmailContent::where('status', true)->get()->toArray();
        foreach ($emailContents as $emailContent) {
            $this->templates[$emailContent['id']] = $emailContent['name'];
        }
    }

    public function mount(array $settings = []): void
    {
        $this->formData["shop_myshopify_domain"] = $settings["shop_myshopify_domain"];
        $this->formData["unvan"] = $settings["unvan"];
        $this->formData["emails"] = $settings["email"].",".$settings["shop_email"];
        $this->resetFormData();
    }

    public function resetFormData(): void
    {
        $this->formData["template"] = "expired";
        $this->formData["message"] = null;
    }

    public function sendMessage(): void
    {
        try {
            $validated = $this->validate([
                "formData.unvan"  => "required|string",
                "formData.shop_myshopify_domain"  => "required|string",
                "formData.emails"  => "required|string",
                "formData.template"  => "required|string",
            ]);
        } catch (\Exception $error) {
            dd($error);
        }

        $formData = $validated["formData"];
        $formData["emails"] = array_unique(explode(",", $formData["emails"]));
        $response = $this->brevoService->sendTemplateEmail($formData["unvan"], $formData["emails"], $formData["template"]);

        //Mesaj gönderimi başarılı ise
        if (!empty($response["messageId"])) {
            $notificationValues["myshopify_domain"] = $formData["shop_myshopify_domain"];
            $notificationValues["type"] = ProviderTypeConstant::EMAIL_PROVIDER;
            $notificationValues["addresses"] = implode(",", $formData["emails"]);
            $notificationValues["template_id"] = $formData["template"];
            $notificationValues["app"] = AppTypeConstant::E_FATURA;
            $notificationValues["payload"]["message_id"] = $response["messageId"];
            NotificationLog::create($notificationValues);
        }

        $this->closeEmailMessageModal();
        $this->resetFormData();
    }

    public function openEmailMessageModal(): void
    {
        $this->dispatch('open-email-message-modal');
    }

    public function closeEmailMessageModal(): void
    {
        $this->resetFormData();
        $this->dispatch('close-email-message-modal');
    }

    public function render()
    {
        return view('livewire.email-message-modal');
    }
}
