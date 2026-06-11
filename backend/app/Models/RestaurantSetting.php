<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantSetting extends Model
{
    protected $fillable = [
        'restaurant_id',
        'name',
        'logo_path',
        'motto',
        'banner_message',
        'today_special_id',
        'brand_colors',
        'phone',
        'address',
        'operating_hours',
    ];

    protected $casts = [
        'operating_hours' => 'array',
        'brand_colors' => 'array',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function todaySpecial(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'today_special_id');
    }
}
