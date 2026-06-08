<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        return [
            'athlete_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'week_count' => ['required', 'integer', 'min:1', 'max:16'],
            'days_per_week' => ['nullable', 'integer', 'min:1', 'max:7'],
            'date_start' => ['required', 'date'],
            'day_table_layout_id' => [
                'nullable',
                'integer',
                Rule::exists('day_table_layouts', 'id')->where(fn ($query) => $query->where('coach_id', $this->user()?->id)),
            ],
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
            $isOnRoster = $coach->athletes()
                ->where('users.id', $athleteId)
                ->where('users.role', 'athlete')
                ->exists();

            if (! $isOnRoster) {
                $validator->errors()->add('athlete_id', 'Cet athlète n’est pas dans votre roster.');
            }
        });
    }
}
