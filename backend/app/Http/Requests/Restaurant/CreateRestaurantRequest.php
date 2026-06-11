<?php

namespace App\Http\Requests\Restaurant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates restaurant creation request data.
 */
class CreateRestaurantRequest extends FormRequest
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
            'restaurant_name'        => ['required', 'string', 'max:255'],
            'restaurant_description' => ['nullable', 'string', 'max:1000'],
            'cuisine_type'           => ['nullable', 'string', 'max:255'],
            'owner_name'             => ['required', 'string', 'max:255'],
            'email'                  => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'               => ['required', 'string', 'min:8'],
            'password_confirmation'  => ['required', 'string'],
            'phone'                  => ['nullable', 'string', 'max:50'],
            'restaurant_email'       => ['nullable', 'email', 'max:255'],
            'address'                => ['nullable', 'string', 'max:255'],
            'role'                   => ['nullable', 'string'],
            'staff_role'             => ['nullable', 'string'],
        ];
    }
}
