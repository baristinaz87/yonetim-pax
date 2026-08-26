<?php

declare(strict_types=1);

namespace App\Models\Shopify;

use App\Constant\ProviderTypeConstant;
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

    public function getTemplateIdForChannel(string $channel): int|string|null
    {
        return match ($channel) {
            ProviderTypeConstant::WP_PROVIDER => $this->whatsapp_template_id,
            ProviderTypeConstant::EMAIL_PROVIDER => $this->email_template_id,
            default => null,
        };
    }
}
