<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    use HasFactory;

    protected $table = 'shopify_apps';

    protected $fillable = [
        'partner_account_id',
        'name',
        'handle',
        'shopify_app_gid',
        'client_id',
        'client_secret',
        'logo',
        'api_auth_endpoint',
        'get_access_token_endpoint',
        'get_app_data_endpoint',
        'auth_email',
        'auth_password',
        'active',
        'last_synced_at',
    ];

    /**
     * Gizli alanlar: OAuth client_secret + delivery API auth_password.
     */
    protected $hidden = [
        'client_secret',
        'auth_password',
    ];

    protected $casts = [
        'active'         => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function partnerAccount(): BelongsTo
    {
        return $this->belongsTo(PartnerAccount::class, 'partner_account_id');
    }

    public function stores(): HasMany
    {
        return $this->hasMany(StoreApp::class, 'app_id');
    }

    public function activeStores(): HasMany
    {
        return $this->hasMany(StoreApp::class, 'app_id')->where('status', 'active');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'app_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeWithGid($query)
    {
        return $query->whereNotNull('shopify_app_gid');
    }
}
