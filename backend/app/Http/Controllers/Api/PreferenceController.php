<?php

namespace App\Http\Controllers\Api;

use App\Models\Preference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PreferenceController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected bool $allowCustomerMutations = true;

    protected bool $tenantScoped = false;

    protected array $searchable = ['key', 'value'];

    protected array $with = ['user'];

    protected function modelClass(): string
    {
        return Preference::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'key' => [$updating ? 'sometimes' : 'required', 'string', 'max:120'],
            'value' => ['nullable', 'string'],
        ];
    }

    protected function scopedQuery(Request $request): Builder
    {
        return Preference::query()->with($this->with)->where('user_id', $request->user()->id);
    }

    protected function mutateValidated(array $validated, Request $request, ?int $restaurantId, bool $updating = false): array
    {
        $validated['user_id'] = $request->user()->id;

        return $validated;
    }
}
