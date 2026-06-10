<?php

namespace App\Http\Requests;

use App\Models\AthleteBodyWeightEntry;
use Illuminate\Foundation\Http\FormRequest;

class UpsertAthleteBodyWeightRequest extends FormRequest
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
        return [
            'entry_date' => ['nullable', 'date', 'before_or_equal:today'],
            'weight_kg' => [
                'required',
                'numeric',
                'min:'.AthleteBodyWeightEntry::MIN_WEIGHT_KG,
                'max:'.AthleteBodyWeightEntry::MAX_WEIGHT_KG,
            ],
        ];
    }
}
