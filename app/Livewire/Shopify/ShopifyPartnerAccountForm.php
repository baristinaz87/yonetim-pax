<?php

declare(strict_types=1);

namespace App\Livewire\Shopify;

use App\Models\Shopify\PartnerAccount;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Shopify Partner Hesabı')]
class ShopifyPartnerAccountForm extends Component
{
    public ?PartnerAccount $account = null;
    public bool $isEditing = false;

    public string $name = '';
    public string $org_id = '';
    public string $access_token = '';
    public string $api_version = '2026-04';
    public bool $active = true;
    public string $notes = '';

    protected function rules(): array
    {
        $accountId = $this->account?->id;

        return [
            'name'         => [
                'required',
                'string',
                'max:255',
                Rule::unique('shopify_partner_accounts', 'name')->ignore($accountId),
            ],
            'org_id'       => 'required|string|max:255',
            // Düzenleme sırasında token opsiyonel — boş bırakılırsa mevcut korunur
            'access_token' => [$this->isEditing ? 'nullable' : 'required', 'string'],
            'api_version'  => 'required|string|max:20',
            'active'       => 'boolean',
            'notes'        => 'nullable|string|max:2000',
        ];
    }

    public function mount(?int $accountId = null): void
    {
        if ($accountId) {
            $this->account   = PartnerAccount::findOrFail($accountId);
            $this->isEditing = true;

            $this->name         = $this->account->name;
            $this->org_id       = $this->account->org_id;
            $this->access_token = $this->account->access_token;
            $this->api_version  = $this->account->api_version ?: '2026-04';
            $this->active       = $this->account->active;
            $this->notes        = $this->account->notes ?? '';
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name'         => $this->name,
            'org_id'       => $this->org_id,
            'api_version'  => $this->api_version,
            'active'       => $this->active,
            'notes'        => $this->notes ?: null,
        ];

        if ($this->isEditing) {
            // Token alanı boş bırakıldıysa mevcut değeri koru
            if ($this->access_token !== '') {
                $data['access_token'] = $this->access_token;
            }
            $this->account->update($data);
            session()->flash('message', 'Partner hesabı güncellendi.');
        } else {
            $data['access_token'] = $this->access_token;
            PartnerAccount::create($data);
            session()->flash('message', 'Partner hesabı oluşturuldu.');
        }

        return redirect()->route('shopify.partner-accounts');
    }

    public function render(): View
    {
        return view('livewire.shopify.shopify-partner-account-form');
    }
}