<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultRolePermission extends Model
{
    protected $fillable = [
        'role_name',
        'entity_key',
        'can_read',
        'can_write',
    ];

    protected function casts(): array
    {
        return [
            'can_read'  => 'boolean',
            'can_write' => 'boolean',
        ];
    }
}
