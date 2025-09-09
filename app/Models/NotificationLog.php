<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'myshopify_domain',
        'type',
        'phone',
        'template_id',
        'payload',
    ];

    protected $casts = [
        'template_id' => 'integer',
        'payload'     => 'array',
    ];
}
