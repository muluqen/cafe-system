<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'staff_role',
        'restaurant_id',
        'custom_role_id',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_super_admin'    => 'boolean',
        ];
    }

    // ── Helpers ────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function isRestaurantStaff(): bool
    {
        return $this->role === 'restaurant';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    // ── Relationships ──────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(Preference::class);
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(StaffShiftAssignment::class);
    }

    public function orderStatusChanges(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'changed_by');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function customRole(): BelongsTo
    {
        return $this->belongsTo(CustomRole::class);
    }

    /**
     * Get effective permissions for this user by merging:
     * 1. Default role permissions for staff_role
     * 2. Custom role permissions (if custom_role_id set)
     * 3. Per-user RolePermission overrides (on top)
     */
    public function effectivePermissions(): array
    {
        $permissions = [];

        // 1. Start with default permissions for staff_role
        if ($this->staff_role) {
            $defaults = DefaultRolePermission::where('role_name', $this->staff_role)->get();
            foreach ($defaults as $dp) {
                $permissions[$dp->entity_key] = [
                    'can_read'  => $dp->can_read,
                    'can_write' => $dp->can_write,
                ];
            }
        }

        // 2. Override with custom role permissions if assigned
        if ($this->custom_role_id) {
            $customPerms = CustomRolePermission::where('custom_role_id', $this->custom_role_id)->get();
            foreach ($customPerms as $cp) {
                $permissions[$cp->entity_key] = [
                    'can_read'  => $cp->can_read,
                    'can_write' => $cp->can_write,
                ];
            }
        }

        // 3. Apply per-user RolePermission overrides on top
        $userPerms = RolePermission::where('user_id', $this->id)->get();
        foreach ($userPerms as $up) {
            $permissions[$up->entity_key] = [
                'can_read'  => $up->can_read,
                'can_write' => $up->can_write,
            ];
        }

        return $permissions;
    }
}
