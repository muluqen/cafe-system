<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates user registration request data.
 */
class RegisterRequest extends FormRequest
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
            'name'                      => ['required', 'string', 'max:255'],
            'email'                     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'                  => ['required', 'string', 'min:8'],
            'role'                      => ['nullable', 'string'],
            'staff_role'                => ['nullable', 'string'],
            'restaurant_name'           => ['nullable', 'string', 'max:255'],
            'restaurant_description'    => ['nullable', 'string', 'max:1000'],
            'cuisine_type'              => ['nullable', 'string', 'max:255'],
            'phone'                     => ['nullable', 'string', 'max:50'],
            'address'                   => ['nullable', 'string', 'max:255'],
        ];
    }
}
