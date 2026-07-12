<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'weight_class' => ['nullable', 'string', 'max:64'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'profession' => ['nullable', 'string', 'max:120'],
            'years_training' => ['nullable', 'integer', 'min:0', 'max:50'],
            'squat' => ['nullable', 'integer', 'min:0', 'max:999'],
            'bench' => ['nullable', 'integer', 'min:0', 'max:999'],
            'deadlift' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
