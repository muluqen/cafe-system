<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class BaseApiController extends Controller
{
    protected int $perPage = 20;

    /**
     * @var array<int, string>
     */
    protected array $searchable = [];

    /**
     * @var array<int, string>
     */
    protected array $with = [];

    /**
     * @var array<int, string>
     */
    protected array $allowedRoles = ['restaurant', 'customer'];

    /**
     * @var array<int, string>
     */
    protected array $allowedStaffRoles = ['manager', 'cashier', 'barista'];

    /**
     * @var array<int, string>
     */
    protected array $mutableStaffRoles = ['manager'];

    protected bool $allowCustomerMutations = false;

    protected ?string $restaurantColumn = 'restaurant_id';

    protected ?string $restaurantRelation = null;

    protected bool $tenantScoped = true;

    abstract protected function modelClass(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(bool $updating = false): array;

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    protected function mutateValidated(array $validated, Request $request, ?int $restaurantId, bool $updating = false): array
    {
        if ($restaurantId && $this->restaurantColumn) {
            $validated[$this->restaurantColumn] = $restaurantId;
        }

        return $validated;
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureRoleAllowed($request);
        $query = $this->scopedQuery($request);

        $search = trim((string) $request->query('search', ''));
        if ($search !== '' && $this->searchable !== []) {
            $query->where(function ($builder) use ($search): void {
                foreach ($this->searchable as $column) {
                    $builder->orWhere($column, 'like', '%' . $search . '%');
                }
            });
        }

        $perPage = (int) $request->query('per_page', $this->perPage);
        $data = $query->latest()->paginate(max($perPage, 1));

        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureRoleAllowed($request, true);
        $restaurantId = $this->resolveRestaurantContext($request);
        $validated = $request->validate($this->rules());
        $validated = $this->mutateValidated($validated, $request, $restaurantId);

        $modelClass = $this->modelClass();
        /** @var Model $record */
        $record = $modelClass::create($validated);
        $record->load($this->with);

        return response()->json($record, 201);
    }

    public function show(string $id): JsonResponse
    {
        $this->ensureRoleAllowed(request());
        $record = $this->scopedQuery(request())->findOrFail($id);

        return response()->json($record);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->ensureRoleAllowed($request, true);
        $restaurantId = $this->resolveRestaurantContext($request);
        $record = $this->scopedQuery($request)->findOrFail($id);

        $validated = $request->validate($this->rules(true));
        $validated = $this->mutateValidated($validated, $request, $restaurantId, true);
        $record->update($validated);
        $record->load($this->with);

        return response()->json($record);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->ensureRoleAllowed(request(), true);
        $record = $this->scopedQuery(request())->findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Deleted']);
    }

    protected function scopedQuery(Request $request): Builder
    {
        $model = $this->modelClass();
        $query = $model::query()->with($this->with);

        if (!$this->tenantScoped) {
            return $query;
        }

        $restaurantId = $this->resolveRestaurantContext($request);
        if (!$restaurantId) {
            return $query;
        }

        if ($this->restaurantColumn) {
            $query->where($this->restaurantColumn, $restaurantId);
        }

        if ($this->restaurantRelation) {
            $query->whereHas($this->restaurantRelation, function (Builder $builder) use ($restaurantId): void {
                $builder->where('restaurant_id', $restaurantId);
            });
        }

        return $query;
    }

    protected function ensureRoleAllowed(Request $request, bool $mutation = false): void
    {
        /** @var User|null $user */
        $user = $request->user();
        if (!$user) {
            throw new HttpResponseException(response()->json(['message' => 'Unauthenticated'], 401));
        }

        if (!in_array($user->role, $this->allowedRoles, true)) {
            throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
        }

        if ($user->isRestaurantStaff()) {
            $allowedStaffRoles = $mutation ? $this->mutableStaffRoles : $this->allowedStaffRoles;

            if (!in_array((string) $user->staff_role, $allowedStaffRoles, true)) {
                throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
            }
        }

        if ($mutation && $user->isCustomer() && !$this->allowCustomerMutations) {
            throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
        }
    }

    protected function resolveRestaurantContext(Request $request): ?int
    {
        if (!$this->tenantScoped) {
            return null;
        }

        /** @var User $user */
        $user = $request->user();

        if ($user->role === 'restaurant') {
            if (!$user->restaurant_id) {
                throw ValidationException::withMessages([
                    'restaurant_id' => 'Restaurant staff account is not linked to a restaurant.',
                ]);
            }

            return (int) $user->restaurant_id;
        }

        $selected = (int) ($request->header('X-Restaurant-Id') ?: $request->query('restaurant_id'));
        if ($selected < 1) {
            throw ValidationException::withMessages([
                'restaurant_id' => 'Select a restaurant first.',
            ]);
        }

        if (!Restaurant::query()->whereKey($selected)->exists()) {
            throw ValidationException::withMessages([
                'restaurant_id' => 'Selected restaurant is invalid.',
            ]);
        }

        return $selected;
    }
}
