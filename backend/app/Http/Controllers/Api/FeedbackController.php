<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles customer feedback for restaurants.
 */
class FeedbackController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected bool $allowCustomerMutations = true;
    protected bool $tenantScoped = false;

    protected array $with = ['restaurant'];

    protected function modelClass(): string
    {
        return Feedback::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'user_id'        => ['nullable', 'exists:users,id'],
            'restaurant_id'  => [$updating ? 'sometimes' : 'required', 'exists:restaurants,id'],
            'rating'         => [$updating ? 'sometimes' : 'required', 'integer', 'min:1', 'max:5'],
            'comment'        => ['nullable', 'string', 'max:1000'],
            'compliment'     => ['nullable', 'string', 'max:1000'],
            'complaint'      => ['nullable', 'string', 'max:1000'],
            'note'           => ['nullable', 'string', 'max:1000'],
            'tags'           => ['nullable', 'array'],
            'tags.*'         => ['string', 'max:50'],
            'customer_name'  => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Customers see only their own feedback; restaurant staff see feedback for their restaurant.
     */
    protected function scopedQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $user = $request->user();

        if ($user->isCustomer()) {
            return Feedback::query()->with($this->with)->where('user_id', $user->id);
        }

        if ($user->isRestaurantStaff() && $user->restaurant_id) {
            return Feedback::query()->with($this->with)->where('restaurant_id', $user->restaurant_id);
        }

        return Feedback::query()->with($this->with);
    }

    /**
     * Override store to handle upsert (one feedback per user per restaurant).
     */
    public function store(Request $request): JsonResponse
    {
        $this->ensureRoleAllowed($request, true);
        $validated = $request->validate($this->rules());
        $validated['user_id'] = $request->user()->id;

        $existing = Feedback::where('user_id', $validated['user_id'])
            ->where('restaurant_id', $validated['restaurant_id'])
            ->first();

        if ($existing) {
            $existing->update($validated);
            $existing->load($this->with);
            return ApiResponse::success($existing, 'Feedback updated');
        }

        $record = Feedback::create($validated);
        $record->load($this->with);

        return ApiResponse::success($record, 'Feedback submitted', 201);
    }

    /**
     * Override index to allow unauthenticated listing for public feedback.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->scopedQuery($request);

        $restaurantId = $request->query('restaurant_id');
        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }

        $data = $query->latest()->paginate((int) $request->query('per_page', 20));

        return ApiResponse::success($data);
    }
}
