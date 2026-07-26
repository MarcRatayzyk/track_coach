<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAthleteProfileFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountSetupRequest extends FormRequest
{
    use ValidatesAthleteProfileFields;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return array_merge($this->athleteProfileFieldRules(), [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'squat' => ['nullable', 'integer', 'min:0', 'max:999'],
            'bench' => ['nullable', 'integer', 'min:0', 'max:999'],
            'deadlift' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'L’adresse e-mail n’est pas valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins :min caractères.',
        ];
    }
}
