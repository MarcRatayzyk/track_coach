<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClearProgramSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        return [
            'week_number' => ['required', 'integer', 'min:1'],
            'weekday' => ['required', 'integer', 'min:1', 'max:7'],
        ];
    }
}
