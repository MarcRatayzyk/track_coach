<?php

namespace App\Http\Requests;

use App\Models\AthleteReadinessEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertAthleteReadinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $athlete = $this->route('athlete');

        return $this->user()?->id === $athlete?->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $scoreRange = Rule::in(range(AthleteReadinessEntry::MIN_SCORE, AthleteReadinessEntry::MAX_SCORE));

        return [
            'entry_date' => ['nullable', 'date', 'before_or_equal:today'],
            'sleep_score' => ['required', 'integer', $scoreRange],
            'stress_score' => ['required', 'integer', $scoreRange],
            'motivation_score' => ['required', 'integer', $scoreRange],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
