<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnAthleteProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $athlete = $this->route('athlete');

        return $this->user()?->id === $athlete?->id
            && $athlete?->role === 'athlete';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'weight_class' => ['nullable', 'string', 'max:64'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'profession' => ['nullable', 'string', 'max:120'],
            'years_training' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }
}
