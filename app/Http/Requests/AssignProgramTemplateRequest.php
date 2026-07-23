<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignProgramTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('date_end') === '' || $this->input('date_end') === null) {
            $this->merge(['date_end' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'athlete_id' => ['required', 'integer', 'exists:users,id'],
            'date_start' => ['required', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $coach = $this->user();
            if ($coach === null) {
                return;
            }

            $isOnRoster = $coach->athletes()
                ->where('users.id', (int) $this->input('athlete_id'))
                ->where('users.role', 'athlete')
                ->exists();

            if (! $isOnRoster) {
                $validator->errors()->add('athlete_id', 'Cet athlète n’est pas dans votre roster.');
            }
        });
    }
}
