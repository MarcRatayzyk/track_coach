<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreCoachRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'plan' => ['nullable', 'string', 'in:starter,growth,scale'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('messages.validation.email_unique_register'),
            'password.confirmed' => __('messages.validation.password_confirmed'),
            'password.min' => __('messages.validation.password_min'),
            'password.mixed' => __('messages.validation.password_mixed'),
            'password.numbers' => __('messages.validation.password_numbers'),
        ];
    }
}
