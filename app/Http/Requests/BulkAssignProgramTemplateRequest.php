<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignProgramTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        return [
            'athlete_ids' => ['required', 'array', 'min:1'],
            'athlete_ids.*' => ['integer', 'exists:users,id'],
            'date_start' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $coach = $this->user();
            if ($coach === null) {
                return;
            }

            $rosterIds = $coach->athletes()
                ->where('users.role', 'athlete')
                ->pluck('users.id')
                ->all();

            foreach ((array) $this->input('athlete_ids', []) as $index => $athleteId) {
                if (! in_array((int) $athleteId, $rosterIds, true)) {
                    $validator->errors()->add(
                        "athlete_ids.{$index}",
                        'Un athlète sélectionné n’est pas dans votre roster.',
                    );
                }
            }
        });
    }
}
