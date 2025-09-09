<?php

namespace App\Livewire;

use App\Models\NotificationLog;
use App\Services\BrevoService;
use Livewire\Component;

class WpMessageModal extends Component
{
    private BrevoService $brevoService;
    public array $formData = [];
    public array $templates = [
        "3" => "E-Fatura Kontör Bitiş Mesajı",
        "4" => "E-Fatura Yeni Müşteri Mesajı",
    ];

    public function __construct()
    {
        $this->brevoService = new BrevoService();
    }

    public function mount(array $settings = []): void
    {
        $this->formData["shop_myshopify_domain"] = $settings["shop_myshopify_domain"];
        $this->formData["phones"] = $settings["phone"].",".$settings["mobile"];
        $this->resetFormData();
    }

    public function resetFormData(): void
    {
        $this->formData["template"] = "3";
        $this->formData["message"] = null;
    }

    public function sendMessage(): void
    {
        try {
            $validated = $this->validate([
                "formData.shop_myshopify_domain"  => "required|string",
                "formData.phones"  => "required|string",
                "formData.template"  => "required|string",
                "formData.message"  => "string|nullable",
            ]);
        } catch (\Exception $error) {
            dd($error);
        }

        $validated["formData"]["phones"] = array_unique(explode(",", $validated["formData"]["phones"]));
        $validated["formData"]["phones"] = array_map(function ($phone) {
            return "90".trim($phone);
        }, $validated["formData"]["phones"]);

        $notificationValues = [];
        if (empty($validated["formData"]["message"])) {
            $response = $this->brevoService
                ->sendTemplateMessage($validated["formData"]["phones"], $validated["formData"]["template"]);
            $notificationValues["template_id"] = $validated["formData"]["template"];
        } else {
            $response = $this->brevoService
                ->sendCustomMessage($validated["formData"]["phones"], $validated["formData"]["message"]);
            $notificationValues["template_id"] = null;
            $notificationValues["payload"]["message"] = $validated["formData"]["message"];
        }

        //Mesaj gönderimi başarılı ise
        if (!empty($response["messageId"])) {
            $notificationValues["myshopify_domain"] = $validated["formData"]["shop_myshopify_domain"];
            $notificationValues["phone"] = implode(",", $validated["formData"]["phones"]);
            $notificationValues["type"] = BrevoService::WP_PROVIDER;
            $notificationValues["payload"]["message_id"] = $response["messageId"];
            NotificationLog::create($notificationValues);
        }

        $this->closeWpMessageModal();
        $this->resetFormData();
    }

    public function openWpMessageModal(): void
    {
        $this->dispatch('open-wp-message-modal');
    }

    public function closeWpMessageModal(): void
    {
        $this->resetFormData();
        $this->dispatch('close-wp-message-modal');
    }

    public function render()
    {
        return view('livewire.wp-message-modal');
    }
}
