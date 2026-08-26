<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WpContent extends Model
{
    protected $fillable = [
        'name',
        'brevo_template_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
