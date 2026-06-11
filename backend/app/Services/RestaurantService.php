<?php

namespace App\Services;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Handles restaurant CRUD, public listing, and scoped queries.
 */
class RestaurantService
{
    /**
     * List all active restaurants (public).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listPublic(): \Illuminate\Database\Eloquent\Collection
    {
        return Restaurant::query()
            ->where('is_active', true)
            ->with('settings')
            ->orderBy('name')
            ->get()
            ->makeHidden(['created_at', 'updated_at', 'phone', 'email', 'address', 'is_active']);
    }

    /**
     * Paginate restaurants scoped to the user.
     *
     * @param  User  $user
     * @param  int   $perPage
     * @return LengthAwarePaginator
     */
    public function list(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $query = Restaurant::query();

        if ($user->role === 'restaurant') {
            $query->whereKey($user->restaurant_id);
            return $query->paginate(1);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get a single restaurant by ID.
     *
     * @param  string       $id
     * @return Restaurant
     */
    public function find(string $id): Restaurant
    {
        return Restaurant::query()->findOrFail($id);
    }

    /**
     * Create a new restaurant.
     *
     * @param  array<string, mixed> $data
     * @return Restaurant
     */
    public function create(array $data): Restaurant
    {
        return Restaurant::query()->create($data);
    }

    /**
     * Update an existing restaurant.
     *
     * @param  string              $id
     * @param  array<string, mixed> $data
     * @return Restaurant
     */
    public function update(string $id, array $data): Restaurant
    {
        $restaurant = Restaurant::query()->findOrFail($id);
        $restaurant->update($data);
        return $restaurant;
    }

    /**
     * Check if the user is authorized to access this restaurant.
     *
     * @param  User       $user
     * @param  string|int $restaurantId
     * @return bool
     */
    public function userHasAccess(User $user, string|int $restaurantId): bool
    {
        if ($user->role === 'restaurant') {
            return (int) $user->restaurant_id === (int) $restaurantId;
        }
        return true;
    }
}
