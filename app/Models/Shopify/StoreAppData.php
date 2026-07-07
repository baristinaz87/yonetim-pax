<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bir mağaza × uygulama ikilisi için uygulamanın topladığı
 * ham veri payload'u (JSON). Her uygulama kendi formatında yazar.
 *
 * Örnek: ShopifyAdminProvider → shop.json içeriği
 *        FlowProvider         → flow metadata
 *        MerchantProvider     → merchant profil verisi
 */
class StoreAppData extends Model
{
    use HasFactory;

    protected $table = 'shopify_store_app_data';

    protected $fillable = [
        'store_id',
        'app_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /* -----------------------------------------------------------------
     |  İlişkiler
     | ----------------------------------------------------------------- */

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    /* -----------------------------------------------------------------
     |  Yardımcı metodlar
     | ----------------------------------------------------------------- */

    /**
     * JSON içinden değer oku (dot-notation destekli).
     * Örn: ->get('email') veya ->get('billing.address.city')
     */
    public function get(string $path, mixed $default = null): mixed
    {
        return data_get($this->data ?? [], $path, $default);
    }

    public function has(string $path): bool
    {
        return data_get($this->data ?? [], $path) !== null;
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }
}