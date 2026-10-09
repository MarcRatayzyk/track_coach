<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignProgramTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canProgramTraining() === true;
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

            $athleteId = (int) $this->input('athlete_id');

            if ($coach->isSelfCoached()) {
                if ($athleteId !== (int) $coach->id) {
                    $validator->errors()->add('athlete_id', __('messages.validation.athlete_not_in_roster'));
                }

                return;
            }

            $isOnRoster = $coach->athletes()
                ->where('users.id', $athleteId)
                ->where('users.role', 'athlete')
                ->exists();

            if (! $isOnRoster) {
                $validator->errors()->add('athlete_id', __('messages.validation.athlete_not_in_roster'));
            }
        });
    }
}
