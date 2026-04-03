<?php

namespace App\Http\Controllers\Api;

use App\Models\PaymentEvent;

class PaymentEventController extends BaseApiController
{
    protected array $allowedRoles = ['restaurant', 'customer'];
    protected array $allowedStaffRoles = ['manager', 'cashier'];
    protected array $mutableStaffRoles = ['manager', 'cashier'];

    protected ?string $restaurantColumn = null;

    protected ?string $restaurantRelation = 'order';

    protected array $searchable = ['method', 'provider', 'provider_reference', 'status'];

    protected array $with = ['order'];

    protected function modelClass(): string
    {
        return PaymentEvent::class;
    }

    protected function rules(bool $updating = false): array
    {
        return [
            'order_id' => [$updating ? 'sometimes' : 'required', 'exists:orders,id'],
            'amount' => [$updating ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'method' => [$updating ? 'sometimes' : 'required', 'string', 'max:50'],
            'provider' => ['nullable', 'string', 'max:100'],
            'provider_reference' => ['nullable', 'string', 'max:150'],
            'status' => ['sometimes', 'string', 'max:40'],
            'paid_at' => ['nullable', 'date'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
