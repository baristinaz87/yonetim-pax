<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Models\Shopify\App;
use App\Models\Shopify\PartnerAccount;
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
    public bool $active = true;

    protected $rules = [
        'partner_account_id' => 'required|integer|exists:shopify_partner_accounts,id',
        'name'               => 'required|string|max:255',
        'handle'             => 'required|string|max:255|alpha_dash|unique:shopify_apps,handle',
        'shopify_app_gid'    => 'nullable|string|max:255|unique:shopify_apps,shopify_app_gid',
        'client_id'          => 'required|string|max:255',
        'client_secret'      => 'required|string',
        'logo'               => 'nullable|string|max:255',
        'active'             => 'boolean',
    ];

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
            $this->active             = $this->app->active;

            // Unique kurallarını mevcut kayıt hariç tut
            $this->rules['handle']          = 'required|string|max:255|alpha_dash|unique:shopify_apps,handle,'
                .$this->app->id;
            $this->rules['shopify_app_gid'] = 'nullable|string|max:255|unique:shopify_apps,shopify_app_gid,'
                .$this->app->id;
            // Düzenleme sırasında secret opsiyonel — boş bırakılırsa mevcut korunur
            $this->rules['client_secret']   = 'nullable|string';
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
            'client_id'          => $this->client_id,
            'logo'               => $this->logo ?: null,
            'active'             => $this->active,
        ];

        if ($this->isEditing) {
            if ($this->client_secret !== '') {
                $data['client_secret'] = $this->client_secret;
            }
            $this->app->update($data);
            session()->flash('message', 'Uygulama güncellendi.');
        } else {
            $data['client_secret'] = $this->client_secret;
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