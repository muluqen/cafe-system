<?php

namespace App\Services;

use App\Models\PlatformNotification;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\RbacService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Handles authentication logic: registration, login, logout, token issuance.
 */
class AuthService
{
    /**
     * List active restaurants for the registration/login selection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function listRestaurants(): \Illuminate\Support\Collection
    {
        return Restaurant::query()
            ->select(['id', 'name', 'slug'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Register a new user (customer or restaurant staff).
     *
     * @param  array<string, mixed> $data
     * @return array{token: string, user: array}
     */
    public function register(array $data): array
    {
        $data['email'] = strtolower(trim($data['email']));

        $hasRestaurant = !empty($data['restaurant_name']);

        if ($hasRestaurant) {
            $user = DB::transaction(function () use ($data): User {
                $restaurant = Restaurant::query()->create([
                    'name'         => $data['restaurant_name'],
                    'slug'         => $this->generateUniqueSlug($data['restaurant_name']),
                    'cuisine_type' => $data['cuisine_type'] ?? null,
                    'phone'        => $data['phone'] ?? null,
                    'email'        => $data['email'],
                    'address'      => $data['address'] ?? null,
                    'is_active'    => false,
                    'status'       => 'pending',
                ]);

                PlatformNotification::create([
                    'type'  => 'new_registration',
                    'title' => 'New Restaurant Registration',
                    'body'  => "{$restaurant->name} has registered and is awaiting approval.",
                    'data'  => ['restaurant_id' => $restaurant->id, 'restaurant_name' => $restaurant->name],
                ]);

                return User::query()->create([
                    'name'          => $data['name'],
                    'email'         => $data['email'],
                    'password'      => Hash::make($data['password']),
                    'role'          => 'restaurant',
                    'staff_role'    => $data['staff_role'] ?? 'manager',
                    'restaurant_id' => $restaurant->id,
                ]);
            });
        } else {
            $user = User::query()->create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => $data['role'] ?? 'customer',
            ]);
        }

        return $this->issueToken($user);
    }

    /**
     * Register a new restaurant with its owner account.
     *
     * @param  array<string, mixed> $data
     * @return array{token: string, user: array}
     */
    public function registerRestaurant(array $data): array
    {
        $data['email'] = strtolower(trim($data['email']));
        $data['restaurant_email'] = isset($data['restaurant_email'])
            ? strtolower(trim((string) $data['restaurant_email']))
            : null;

        $user = DB::transaction(function () use ($data): User {
            $restaurant = Restaurant::query()->create([
                'name'      => $data['restaurant_name'],
                'slug'      => $this->generateUniqueSlug($data['restaurant_name']),
                'phone'     => $data['phone'] ?? null,
                'email'     => $data['restaurant_email'] ?? $data['email'],
                'address'   => $data['address'] ?? null,
                'is_active' => false,
                'status'    => 'pending',
            ]);

            PlatformNotification::create([
                'type'  => 'new_registration',
                'title' => 'New Restaurant Registration',
                'body'  => "{$restaurant->name} has registered and is awaiting approval.",
                'data'  => ['restaurant_id' => $restaurant->id, 'restaurant_name' => $restaurant->name],
            ]);

            return User::query()->create([
                'name'          => $data['owner_name'],
                'email'         => $data['email'],
                'password'      => Hash::make($data['password']),
                'role'          => 'restaurant',
                'staff_role'    => 'manager',
                'restaurant_id' => $restaurant->id,
            ]);
        });

        return $this->issueToken($user);
    }

    /**
     * Authenticate a user and return a token.
     *
     * @param  array<string, mixed> $data
     * @return array{token: string, user: array}
     */
    public function login(array $data): array
    {
        $email = strtolower(trim($data['email']));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        $isRestaurantAccess = $this->isRestaurantAccess($data['access_key'] ?? null);

        if ($isRestaurantAccess && $user->role !== 'restaurant') {
            throw ValidationException::withMessages([
                'access_key' => 'This access key is only for restaurant team accounts.',
            ]);
        }

        if (!$isRestaurantAccess && $user->role !== 'customer') {
            // Allow super admin to login without access key
            if (!($user->is_super_admin ?? false)) {
                throw ValidationException::withMessages([
                    'access_key' => 'Restaurant team accounts must use the restaurant access key.',
                ]);
            }
        }

        // Skip restaurant_id check for super admin
        if ($user->role === 'restaurant' && !$user->restaurant_id && !($user->is_super_admin ?? false)) {
            throw ValidationException::withMessages([
                'access_key' => 'Restaurant account is not linked to a restaurant.',
            ]);
        }

        // Check restaurant approval status (skip for super admin)
        if ($user->role === 'restaurant' && $user->restaurant_id && !($user->is_super_admin ?? false)) {
            $restaurant = Restaurant::find($user->restaurant_id);

            if ($restaurant && $restaurant->status === 'pending') {
                throw ValidationException::withMessages([
                    'email' => 'Your restaurant is pending approval. Please wait for a super admin to approve your account.',
                ]);
            }

            if ($restaurant && $restaurant->status === 'suspended') {
                throw ValidationException::withMessages([
                    'email' => 'Your restaurant has been suspended. Please contact support.',
                ]);
            }
        }

        return $this->issueToken($user);
    }

    /**
     * Get the currently authenticated user.
     *
     * @param  User $user
     * @return User
     */
    public function me(User $user): User
    {
        return $user;
    }

    /**
     * Revoke the current access token.
     *
     * @param  User $user
     * @return void
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * Validate the restaurant access key.
     *
     * @param  string|null $key
     * @return bool
     */
    private function isRestaurantAccess(?string $key): bool
    {
        if (!$key) {
            return false;
        }

        if (!hash_equals((string) config('app.restaurant_access_key'), $key)) {
            throw ValidationException::withMessages([
                'access_key' => 'Invalid restaurant access key.',
            ]);
        }

        return true;
    }

    /**
     * Issue a Sanctum token and return the formatted response.
     *
     * @param  User  $user
     * @return array{token: string, user: array}
     */
    private function issueToken(User $user): array
    {
        $token = $user->createToken('api-token')->plainTextToken;

        // Include effective RBAC permissions in response
        $rbacService = new RbacService();
        $permissions = $rbacService->getEffectivePermissions($user);

        return [
            'token' => $token,
            'user'  => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'role'            => $user->role,
                'staff_role'      => $user->staff_role,
                'restaurant_id'   => $user->restaurant_id,
                'custom_role_id'  => $user->custom_role_id,
                'is_super_admin'  => $user->is_super_admin ?? false,
            ],
            'permissions' => $permissions,
        ];
    }

    /**
     * Generate a unique slug for a restaurant.
     *
     * @param  string $name
     * @return string
     */
    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'restaurant';
        $slug = $base;
        $suffix = 2;

        while (Restaurant::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
