<?php

namespace App\Http\Requests;

use App\Models\AthleteProfile;
use App\Support\ReadinessFormSupport;
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
        $fieldRules = ReadinessFormSupport::fieldsValidationRules();
        $fieldRules['fields'] = ['sometimes', 'array', 'min:1', 'max:30'];

        return array_merge([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'feedback_frequency' => [
                'required',
                'string',
                Rule::in([AthleteProfile::FREQUENCY_DAILY, AthleteProfile::FREQUENCY_WEEKLY]),
            ],
        ], $fieldRules);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
        ];
    }
}
