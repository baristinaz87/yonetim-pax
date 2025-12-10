<?php

namespace App\Livewire;

use App\Client\EFaturaClient;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class MerchantDetailForm extends Component
{
    private EFaturaClient $eFaturaClient;
    private GoogleCalendarService $googleCalendarService;
    public int $merchantId;
    public array $data = [];
    public array $initialData = [];
    public array $creditFormData = [];

    public function __construct()
    {
        $this->eFaturaClient = new EfaturaClient();
        $this->googleCalendarService = new GoogleCalendarService();
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
        if (!empty($setting['created_at']))
            $setting['created_at'] = Carbon::parse($setting['created_at'])->format('Y-m-d');
        $setting["app_updated_at"] = $response['data']['password_updated_at'] ?? null;

        $this->data = array_merge($this->data, $setting);
        $this->initialData = $this->data;
        $this->creditFormData["shop_name"] = $setting["shop_name"];
    }

    public function addCredit(): void
    {
        $validated = $this->validate([
            "creditFormData.credit"  => "required|integer",
            "creditFormData.amount"  => "required|numeric",
            "creditFormData.description"  => "required|string",
        ]);

        $validated["creditFormData"]["user_id"] = $this->merchantId;
        $this->eFaturaClient->addCredit($validated["creditFormData"]);
        session()->flash('addCreditMessage', 'Kredi başarıyla yüklendi.');
        $this->dispatch('close-add-credit-modal');
        $this->dispatch('credit-added');
        $this->resetExcept('merchantId');
    }

    public function updateCreditFields(): void
    {
        $validated = $this->validate([
            "data.credit_expired_at"  => "nullable|string",
            "data.credit_tracking_at" => "nullable|string",
            "data.auto_load_credit" => "required|integer",
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
            "data.auto_send" => "required",
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

    public function openAddCreditModal(): void
    {
        $this->dispatch('open-add-credit-modal');
    }

    public function closeAddCreditModal(): void
    {
        $this->dispatch('close-add-credit-modal');
    }

    /**
     * Add credit expiration reminder to Google Calendar
     */
    public function addToGoogleCalendar(): void
    {
        try {
            // Check if credit expired date is set
            if (empty($this->data['credit_expired_at'])) {
                session()->flash('calendarMessage', 'Kontör bitiş tarihi ayarlanmamış.');
                return;
            }

            // Check if Google access token exists in session
            $accessToken = Session::get('google_access_token');
            if (!$accessToken) {
                // Redirect to Google OAuth using JavaScript
                $authUrl = $this->googleCalendarService->getAuthUrl();
                $this->dispatch('redirect-to-google', url: $authUrl);
                return;
            }

            // Create calendar event
            $shopName = $this->data['shop_name'] ?? 'Mağaza';
            $creditExpiredAt = $this->data['credit_expired_at'];

            $event = $this->googleCalendarService->createCreditReminderEvent(
                $shopName,
                $creditExpiredAt,
                $accessToken
            );

            session()->flash('calendarMessage', 'Kontör hatırlatma takviminize eklendi.');
            $this->dispatch('calendar-event-added');

        } catch (\Exception $e) {
            session()->flash('calendarError', 'Takvim olayını eklerken hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request): void
    {
        try {
            $code = $request->get('code');
            if (!$code) {
                throw new \Exception('Authorization code not received');
            }

            $accessToken = $this->googleCalendarService->handleCallback($code);

            // Store token in session
            Session::put('google_access_token', $accessToken);

            session()->flash('calendarMessage', 'Google Calendar bağlantısı başarılı. Şimdi takvim olayını ekleyebilirsiniz.');
            $this->dispatch('google-authorized');

        } catch (\Exception $e) {
            session()->flash('calendarError', 'Google yetkilendirme hatası: ' . $e->getMessage());
        }
    }

    /**
     * Get Google OAuth authorization URL
     */
    public function getGoogleAuthUrl(): string
    {
        return $this->googleCalendarService->getAuthUrl();
    }

    public function render(): View
    {
        $this->getData();
        return view('livewire.merchant-detail-form', $this->data);
    }
}
