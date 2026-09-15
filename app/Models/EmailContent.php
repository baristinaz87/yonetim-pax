<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailContent extends Model
{
    protected $fillable = [
        'name',
        'subject',
        'content',
        'brevo_template_id',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'brevo_template_id' => 'integer',
        ];
    }
}
