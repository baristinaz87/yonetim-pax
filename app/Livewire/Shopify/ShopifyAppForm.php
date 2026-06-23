<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Models\Shopify\App;
use App\Models\Shopify\PartnerAccount;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Shopify Uygulaması')]
class ShopifyAppForm extends Component
{
    public ?App $app = null;
    public bool $isEditing = false;

    public ?int $partner_account_id = null;
    public string $name = '';
    public string $handle = '';
    public string $shopify_app_gid = '';
    public string $client_id = '';
    public string $client_secret = '';
    public string $logo = '';
    public string $api_auth_endpoint = '';
    public string $get_access_token_endpoint = '';
    public string $auth_email = '';
    public string $auth_password = '';
    public bool $active = true;

    /**
     * Dinamik kurallar: mount() içinde $this->app set edildikten sonra
     * ignore değerini doğru hesaplayabiliriz.
     */
    protected function rules(): array
    {
        $appId = $this->app?->id;

        return [
            'partner_account_id' => 'required|integer|exists:shopify_partner_accounts,id',
            'name'               => 'required|string|max:255',
            'handle'             => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('shopify_apps', 'handle')->ignore($appId),
            ],
            'shopify_app_gid'    => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('shopify_apps', 'shopify_app_gid')->ignore($appId),
            ],
            'client_id'          => 'required|string|max:255',
            // Düzenleme sırasında secret opsiyonel — boş bırakılırsa mevcut korunur
            'client_secret'      => [$this->isEditing ? 'nullable' : 'required', 'string'],
            'logo'               => 'nullable|string|max:255',
            'api_auth_endpoint'  => 'nullable|url|max:500',
            'get_access_token_endpoint' => 'nullable|url|max:500',
            'auth_email'         => 'nullable|email|max:255',
            // Düzenleme sırasında password opsiyonel — boş bırakılırsa mevcut korunur
            'auth_password'      => [$this->isEditing ? 'nullable' : 'required', 'string', 'max:1000'],
            'active'             => 'boolean',
        ];
    }

    public function mount(?int $appId = null): void
    {
        if ($appId) {
            $this->app       = App::findOrFail($appId);
            $this->isEditing = true;

            $this->partner_account_id = $this->app->partner_account_id;
            $this->name               = $this->app->name;
            $this->handle             = $this->app->handle;
            $this->shopify_app_gid    = $this->app->shopify_app_gid ?? '';
            $this->client_id          = $this->app->client_id;
            $this->client_secret      = $this->app->client_secret;
            $this->logo               = $this->app->logo ?? '';
            $this->api_auth_endpoint  = $this->app->api_auth_endpoint ?? '';
            $this->get_access_token_endpoint = $this->app->get_access_token_endpoint ?? '';
            $this->auth_email         = $this->app->auth_email ?? '';
            $this->auth_password      = $this->app->auth_password ?? '';
            $this->active             = $this->app->active;
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'partner_account_id' => $this->partner_account_id,
            'name'               => $this->name,
            'handle'             => $this->handle,
            'shopify_app_gid'    => $this->shopify_app_gid ?: null,
            'client_id'             => $this->client_id,
            'logo'                  => $this->logo ?: null,
            'api_auth_endpoint'     => $this->api_auth_endpoint ?: null,
            'get_access_token_endpoint' => $this->get_access_token_endpoint ?: null,
            'auth_email'            => $this->auth_email ?: null,
            'active'                => $this->active,
        ];

        if ($this->isEditing) {
            // Token alanları boş bırakıldıysa mevcut değerleri koru
            if ($this->client_secret !== '') {
                $data['client_secret'] = $this->client_secret;
            }
            if ($this->auth_password !== '') {
                $data['auth_password'] = $this->auth_password;
            }
            $this->app->update($data);
            session()->flash('message', 'Uygulama güncellendi.');
        } else {
            $data['client_secret'] = $this->client_secret;
            $data['auth_password'] = $this->auth_password ?: null;
            App::create($data);
            session()->flash('message', 'Uygulama oluşturuldu.');
        }

        return redirect()->route('shopify.apps');
    }

    public function render(): View
    {
        $partners = PartnerAccount::query()
            ->orderBy('name')
            ->get(['id', 'name', 'org_id', 'active']);

        return view('livewire.shopify.shopify-app-form', [
            'partners' => $partners,
        ]);
    }
}