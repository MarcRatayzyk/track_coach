<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAthleteProfileFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountSetupRequest extends FormRequest
{
    use ValidatesAthleteProfileFields;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->athleteProfileFieldRules(), [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'squat' => ['nullable', 'integer', 'min:0', 'max:999'],
            'bench' => ['nullable', 'integer', 'min:0', 'max:999'],
            'deadlift' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
