<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreApp extends Model
{
    use HasFactory;

    protected $table = 'shopify_store_apps';

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'app_id',
        'access_token',
        'installed_at',
        'uninstalled_at',
        'status',
    ];

    protected $casts = [
        'installed_at'   => 'datetime',
        'uninstalled_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    /**
     * Kayıttaki son durum değişikliği (kurulum ya da kaldırma) verilen andan sonra mı?
     *
     * Webhook ve Partner API olayları sıra dışı bildirebilir (ör. eski bir kaldırma,
     * sonraki yeniden kurulumdan sonra gelir). true ise o andaki olay geçmişe aittir
     * ve güncel durumu/token'ı ezmemelidir.
     */
    public function changedAfter(CarbonInterface $at): bool
    {
        $lastChangeAt = $this->status === 'active' ? $this->installed_at : $this->uninstalled_at;

        return $lastChangeAt !== null && $lastChangeAt->gt($at);
    }
}
