<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flow extends Model
{
    use HasFactory;

    protected $table = 'shopify_flows';

    protected $fillable = [
        'name',
        'event_type',
        'app_ids',
        'channels',
        'delay_minutes',
        'whatsapp_template_id',
        'email_template_id',
        'active',
    ];

    protected $casts = [
        'app_ids'       => 'array',
        'channels'      => 'array',
        'delay_minutes' => 'integer',
        'active'        => 'boolean',
    ];

    public function matches(Event $event): bool
    {
        if (! $this->active || $this->event_type !== $event->type || ! $event->app_id) {
            return false;
        }

        return in_array((int) $event->app_id, array_map('intval', $this->app_ids ?? []), true);
    }
}
