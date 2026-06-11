<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Handles user/staff CRUD for restaurant owners.
 */
class UserController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager'];
    protected array $mutableStaffRoles = ['manager'];
    protected bool $allowCustomerMutations = true;

    protected bool $tenantScoped = false;

    protected array $searchable = ['name', 'email'];

    protected array $with = ['permissions'];

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
            'name'          => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'email'         => [
                $updating ? 'sometimes' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password'      => [$updating ? 'sometimes' : 'required', 'string', 'min:8'],
            'role'          => ['nullable', 'in:restaurant,customer'],
            'staff_role'    => ['nullable', 'in:manager,floor_manager,host,server,cashier,barista,kitchen,inventory'],
            'restaurant_id' => ['nullable', 'exists:restaurants,id'],
        ];
    }

    /**
     * Mutate validated data for user creation/update.
     */
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

            if ($updating && (int) $request->user()->id === (int) $targetUserId) {
                unset($validated['staff_role']);
            }
        } else {
            $validated['role'] = $validated['role'] ?? 'customer';
            $validated['staff_role'] = null;
        }

        return $validated;
    }

    /**
     * Scope users to the restaurant or to the customer themselves.
     */
    protected function scopedQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        if ($request->user()->role === 'restaurant') {
            return User::query()->where('restaurant_id', $request->user()->restaurant_id);
        }

        return User::query()->whereKey($request->user()->id);
    }

    /**
     * Create a user with optional permissions.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'restaurant') {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate($this->rules());
        $validated = $this->mutateValidated($validated, $request, $restaurantId);

        $record = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request, $restaurantId) {
            $user = User::create($validated);

            if ($request->has('permissions')) {
                foreach ($request->input('permissions') as $perm) {
                    \App\Models\RolePermission::updateOrCreate(
                        [
                            'restaurant_id' => $restaurantId,
                            'user_id'       => $user->id,
                            'entity_key'    => $perm['entity_key'],
                        ],
                        [
                            'staff_role' => $user->staff_role,
                            'can_read'   => $perm['can_read'],
                            'can_write'  => $perm['can_write'],
                        ]
                    );
                }
            }
            return $user;
        });

        $record->load($this->with);

        return ApiResponse::success($record, 'Created', 201);
    }

    /**
     * Update a user with optional permissions.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureRoleAllowed($request, true);
        $restaurantId = $this->resolveRestaurantContext($request);
        $record = $this->scopedQuery($request)->findOrFail($id);

        $validated = $request->validate($this->rules(true));
        $validated = $this->mutateValidated($validated, $request, $restaurantId, true);

        $record = \Illuminate\Support\Facades\DB::transaction(function () use ($record, $validated, $request, $restaurantId) {
            $record->update($validated);

            if ($request->has('permissions')) {
                foreach ($request->input('permissions') as $perm) {
                    \App\Models\RolePermission::updateOrCreate(
                        [
                            'restaurant_id' => $restaurantId,
                            'user_id'       => $record->id,
                            'entity_key'    => $perm['entity_key'],
                        ],
                        [
                            'staff_role' => $record->staff_role,
                            'can_read'   => $perm['can_read'],
                            'can_write'  => $perm['can_write'],
                        ]
                    );
                }
            }
            return $record;
        });

        $record->load($this->with);

        return ApiResponse::success($record, 'Updated');
    }

    /**
     * Delete a user.
     */
    public function destroy(string $id): JsonResponse
    {
        if (request()->user()->role !== 'restaurant') {
            return ApiResponse::error('Forbidden', null, 403);
        }

        return parent::destroy($id);
    }
}
