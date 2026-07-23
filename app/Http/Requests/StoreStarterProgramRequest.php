<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStarterProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:100'],
            'athlete_id' => ['required', 'integer', 'exists:users,id'],
            'date_start' => ['required', 'date'],
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
