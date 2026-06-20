<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    protected $table = 'shopify_events';

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'app_id',
        'type',
        'label',
        'data',
        'created_at',
    ];

    protected $casts = [
        'data'       => 'array',
        'created_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }
}
