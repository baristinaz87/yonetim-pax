<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerAccount extends Model
{
    use HasFactory;

    protected $table = 'shopify_partner_accounts';

    protected $fillable = [
        'name',
        'org_id',
        'access_token',
        'api_version',
        'active',
        'notes',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Access token gizli tutulur — array/json döngülerinde maskelenir.
     */
    protected $hidden = [
        'access_token',
    ];

    public function apps(): HasMany
    {
        return $this->hasMany(App::class, 'partner_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function partnerUrl(): string
    {
        return "https://partners.shopify.com/{$this->org_id}";
    }
}