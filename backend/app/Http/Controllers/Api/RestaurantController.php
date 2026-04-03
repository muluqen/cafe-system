<?php

namespace App\Http\Controllers\Api;

use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager'];
    protected array $mutableStaffRoles = ['manager'];

    protected bool $tenantScoped = false;

    protected array $searchable = ['name', 'slug', 'email', 'phone'];

    protected function modelClass(): string
    {
        return Restaurant::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'name' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureRoleAllowed($request);

        if ($request->user()->role === 'restaurant') {
            $data = Restaurant::query()->whereKey($request->user()->restaurant_id)->paginate(1);
            return response()->json($data);
        }

        return parent::index($request);
    }

    public function show(string $id): JsonResponse
    {
        $user = request()->user();
        if ($user->role === 'restaurant' && (int) $user->restaurant_id !== (int) $id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return parent::show($id);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        if ($request->user()->role !== 'restaurant' || (int) $request->user()->restaurant_id !== (int) $id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return parent::update($request, $id);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Forbidden'], 403);
    }
}
