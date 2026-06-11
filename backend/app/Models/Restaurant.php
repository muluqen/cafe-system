<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'phone',
        'email',
        'address',
        'is_active',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'cuisine_type',
        'location',
        'rating',
        'opening_time',
        'closing_time',
        'image_url',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'rating'     => 'decimal:2',
        'opening_time' => 'string',
        'closing_time' => 'string',
        'approved_at' => 'datetime',
    ];

    // ── Scopes ─────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', 'suspended');
    }

    // ── Accessors ──────────────────────────────────────────

    public function getIsOpenAttribute(): bool
    {
        if (!$this->opening_time || !$this->closing_time) {
            return true;
        }

        $now = now()->format('H:i');
        return $now >= $this->opening_time && $now <= $this->closing_time;
    }

    protected $appends = ['is_open'];

    // ── Relationships ──────────────────────────────────────

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(DiningTable::class, 'restaurant_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(RestaurantSetting::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}
