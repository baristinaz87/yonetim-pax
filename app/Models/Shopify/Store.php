<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $table = 'shopify_stores';

    protected $fillable = [
        'domain',
        'shop_id',
        'name',
        'shop_owner',
        'email',
        'contact_email',
        'phone',
        'address1',
        'city',
        'zip',
        'country',
        'country_code',
        'currency',
        'plan_name',
        'plan_display_name',
        'timezone',
        'language',
    ];

    public function apps(): HasMany
    {
        return $this->hasMany(StoreApp::class, 'store_id');
    }

    public function activeApps(): HasMany
    {
        return $this->hasMany(StoreApp::class, 'store_id')->where('status', 'active');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'store_id');
    }

    /**
     * Shopify REST shop.json içeriğini mağaza alanlarına eşler (kaydetmez).
     * Hem Admin API çağrısı hem de uygulamanın webhook ile gönderdiği shop verisi kullanır.
     *
     * @param  array<string, mixed>  $shop
     */
    public function fillFromShopPayload(array $shop): static
    {
        return $this->fill([
            'shop_id'          => (string) ($shop['id'] ?? ''),
            'name'             => $shop['name'] ?? null,
            'shop_owner'       => $shop['shop_owner'] ?? null,
            'email'            => $shop['email'] ?? null,
            'phone'            => $shop['phone'] ?? null,
            'address1'         => $shop['address1'] ?? null,
            'city'             => $shop['city'] ?? null,
            'zip'              => $shop['zip'] ?? null,
            'country'          => $shop['country_name'] ?? null,
            'country_code'     => $shop['country_code'] ?? null,
            'currency'         => $shop['currency'] ?? null,
            'plan_name'        => $shop['plan_name'] ?? null,
            'plan_display_name'=> $shop['plan_display_name'] ?? null,
            'timezone'         => $shop['iana_timezone'] ?? null,
            'language'         => $shop['primary_locale'] ?? null,
        ]);
    }
}
