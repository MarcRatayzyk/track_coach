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
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'feedback_frequency' => [
                'required',
                'string',
                Rule::in([AthleteProfile::FREQUENCY_DAILY, AthleteProfile::FREQUENCY_WEEKLY]),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->input('email')) && trim($this->input('email')) === '') {
            $this->merge(['email' => null]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => __('messages.validation.email_invalid'),
            'email.unique' => __('messages.validation.email_unique'),
            'feedback_frequency.required' => __('messages.validation.feedback_frequency_required'),
            'feedback_frequency.in' => __('messages.validation.feedback_frequency_in'),
        ];
    }
}
