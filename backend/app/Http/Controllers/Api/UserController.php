<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager'];
    protected array $mutableStaffRoles = ['manager'];
    protected bool $allowCustomerMutations = true;

    protected bool $tenantScoped = false;

    protected array $searchable = ['name', 'email'];

    protected function modelClass(): string
    {
        return User::class;
    }

    protected function rules(bool $updating = false): array
    {
        $userId = request()->route('user');
        if (is_object($userId)) {
            $userId = $userId->id;
        }

        return [
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'email' => [
                $updating ? 'sometimes' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [$updating ? 'sometimes' : 'required', 'string', 'min:8'],
            'role' => ['nullable', 'in:restaurant,customer'],
            'staff_role' => ['nullable', 'in:manager,floor_manager,host,server,cashier,barista,kitchen,inventory'],
            'restaurant_id' => ['nullable', 'exists:restaurants,id'],
        ];
    }

    protected function mutateValidated(array $validated, Request $request, ?int $restaurantId, bool $updating = false): array
    {
        if (isset($validated['email'])) {
            $validated['email'] = strtolower(trim((string) $validated['email']));
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $targetUserId = $request->route('user');
        if (is_object($targetUserId)) {
            $targetUserId = $targetUserId->id;
        }

        if ($request->user()->role === 'restaurant') {
            $validated['role'] = 'restaurant';
            $validated['staff_role'] = $validated['staff_role'] ?? 'server';
            $validated['restaurant_id'] = $request->user()->restaurant_id;

            // Prevent staff from demoting their own role by mistake.
            if ($updating && (int) $request->user()->id === (int) $targetUserId) {
                unset($validated['staff_role']);
            }
        } else {
            $validated['role'] = $validated['role'] ?? 'customer';
            $validated['staff_role'] = null;
        }

        return $validated;
    }

    protected function scopedQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        if ($request->user()->role === 'restaurant') {
            return User::query()->where('restaurant_id', $request->user()->restaurant_id);
        }

        return User::query()->whereKey($request->user()->id);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'restaurant') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return parent::store($request);
    }

    public function destroy(string $id): JsonResponse
    {
        if (request()->user()->role !== 'restaurant') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return parent::destroy($id);
    }
}
