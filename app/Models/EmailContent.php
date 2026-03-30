<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailContent extends Model
{
    protected $fillable = [
        'name',
        'subject',
        'content',
        'status'
    ];
}
