<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function restaurants(): JsonResponse
    {
        return response()->json(
            Restaurant::query()
                ->select(['id', 'name', 'slug'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
        );
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'access_key' => ['nullable', 'string', 'max:255'],
            'restaurant_id' => ['nullable', 'integer', 'exists:restaurants,id'],
        ]);

        $validated['email'] = strtolower(trim($validated['email']));

        $isRestaurantAccess = $this->isRestaurantAccess($validated['access_key'] ?? null);

        if ($isRestaurantAccess && empty($validated['restaurant_id'])) {
            throw ValidationException::withMessages([
                'restaurant_id' => 'Restaurant selection is required for team accounts.',
            ]);
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $isRestaurantAccess ? 'restaurant' : 'customer',
            'staff_role' => $isRestaurantAccess ? 'manager' : null,
            'restaurant_id' => $isRestaurantAccess ? $validated['restaurant_id'] : null,
        ]);

        return $this->issueTokenResponse($user, 201);
    }

    public function registerRestaurant(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'access_key' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'restaurant_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['email'] = strtolower(trim($validated['email']));
        $validated['restaurant_email'] = isset($validated['restaurant_email'])
            ? strtolower(trim((string) $validated['restaurant_email']))
            : null;

        $this->isRestaurantAccess($validated['access_key']);

        $user = DB::transaction(function () use ($validated): User {
            $restaurant = Restaurant::query()->create([
                'name' => $validated['restaurant_name'],
                'slug' => $this->generateUniqueRestaurantSlug($validated['restaurant_name']),
                'phone' => $validated['phone'] ?: null,
                'email' => $validated['restaurant_email'] ?: $validated['email'],
                'address' => $validated['address'] ?: null,
                'is_active' => false,
            ]);

            return User::query()->create([
                'name' => $validated['owner_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'restaurant',
                'staff_role' => 'manager',
                'restaurant_id' => $restaurant->id,
            ]);
        });

        return $this->issueTokenResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'access_key' => ['nullable', 'string', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        $isRestaurantAccess = $this->isRestaurantAccess($validated['access_key'] ?? null);

        if ($isRestaurantAccess && $user->role !== 'restaurant') {
            throw ValidationException::withMessages([
                'access_key' => 'This access key is only for restaurant team accounts.',
            ]);
        }

        if (!$isRestaurantAccess && $user->role !== 'customer') {
            throw ValidationException::withMessages([
                'access_key' => 'Restaurant team accounts must use the restaurant access key.',
            ]);
        }

        if ($user->role === 'restaurant' && !$user->restaurant_id) {
            throw ValidationException::withMessages([
                'access_key' => 'Restaurant account is not linked to a restaurant.',
            ]);
        }

        return $this->issueTokenResponse($user);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }

    protected function isRestaurantAccess(?string $key): bool
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

    protected function issueTokenResponse(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'staff_role' => $user->staff_role,
                'restaurant_id' => $user->restaurant_id,
            ],
        ], $status);
    }

    protected function generateUniqueRestaurantSlug(string $name): string
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
