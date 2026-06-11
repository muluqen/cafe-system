<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates restaurant update request data.
 */
class UpdateRestaurantRequest extends FormRequest
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
            'name'      => ['sometimes', 'string', 'max:255'],
            'slug'      => ['nullable', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:255'],
            'address'   => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
