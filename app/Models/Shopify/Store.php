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
}
