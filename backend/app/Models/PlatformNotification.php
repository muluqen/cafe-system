<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data'     => 'array',
        'read_at' => 'datetime',
    ];
}
