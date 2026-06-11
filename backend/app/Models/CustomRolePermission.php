<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomRolePermission extends Model
{
    protected $fillable = [
        'custom_role_id',
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

    public function customRole(): BelongsTo
    {
        return $this->belongsTo(CustomRole::class);
    }
}
