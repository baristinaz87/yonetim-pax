<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
     * Verilen anahtarları mağaza × uygulama kaydının verisine ekler/günceller;
     * diğer anahtarlara dokunmaz. Kayıt yoksa yalnızca bu anahtarlarla açılır,
     * kalanını periyodik senkron (shopify:update-app-shop-data) doldurur.
     *
     * Event generator'ların "Veri Tazeliği" kontrolü updated_at'e baktığı için
     * veri aynı olsa bile zaman damgası tazelenir.
     *
     * @param array<string, mixed> $data
     * @throws \Throwable
     */
    public static function mergeFor(Store $store, App $app, array $data): self
    {
        return DB::transaction(function () use ($store, $app, $data): self {
            $row = self::query()
                ->where('store_id', $store->id)
                ->where('app_id', $app->id)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $row = new self(['store_id' => $store->id, 'app_id' => $app->id]);
            }

            $row->data = array_merge(is_array($row->data) ? $row->data : [], $data);
            $row->updated_at = now();
            $row->save();

            return $row;
        });
    }

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
