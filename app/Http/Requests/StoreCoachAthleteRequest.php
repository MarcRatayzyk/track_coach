<?php

namespace App\Http\Requests;

use App\Models\AthleteProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoachAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'feedback_frequency' => [
                'required',
                'string',
                Rule::in([AthleteProfile::FREQUENCY_DAILY, AthleteProfile::FREQUENCY_WEEKLY]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L’adresse e-mail de l’athlète est obligatoire.',
            'email.email' => 'L’adresse e-mail n’est pas valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'feedback_frequency.required' => 'Choisis le type de coaching.',
            'feedback_frequency.in' => 'Choisis un type de coaching valide.',
        ];
    }
}
