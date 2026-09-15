<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventGenerator extends Model
{
    use HasFactory;

    protected $table = 'shopify_event_generators';

    protected $fillable = [
        'name',
        'handle',
        'app_ids',
        'conditions',
        'condition_logic',
        'cooldown_minutes',
        'max_data_age_minutes',
        'schedule',
        'active',
    ];

    protected $casts = [
        'app_ids' => 'array',
        'conditions' => 'array',
        'schedule' => 'array',
        'cooldown_minutes' => 'integer',
        'max_data_age_minutes' => 'integer',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
