<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'squat' => ['required', 'integer', 'min:0'],
            'bench' => ['required', 'integer', 'min:0'],
            'deadlift' => ['required', 'integer', 'min:0'],
            'reference_date' => ['required', 'date'],
        ];
    }
}
