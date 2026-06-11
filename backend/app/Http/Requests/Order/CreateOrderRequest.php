<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates order creation request data (checkout).
 */
class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'restaurant_id'              => ['required', 'integer', 'exists:restaurants,id'],
            'order_type'                 => ['nullable', 'string', 'in:dine_in,takeaway,delivery'],
            'table_id'                   => ['nullable', 'integer', 'exists:tables,id'],
            'cart'                       => ['required', 'array', 'min:1'],
            'cart.*.menu_item_id'        => ['nullable'],
            'cart.*.name'                => ['required', 'string'],
            'cart.*.quantity'            => ['required', 'numeric', 'min:0.01'],
            'cart.*.price'               => ['required', 'numeric'],
            'cart.*.is_custom'           => ['nullable', 'boolean'],
            'cart.*.notes'               => ['nullable', 'string'],
            'cart.*.customized_ingredients' => ['nullable', 'array'],
            'cart.*.removed_ingredients'    => ['nullable', 'array'],
            'cart.*.removed_ingredients.*'  => ['nullable', 'string'],
            'subtotal'                   => ['required', 'numeric'],
            'tax'                        => ['required', 'numeric'],
            'total'                      => ['required', 'numeric'],
        ];
    }
}
