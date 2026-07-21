<?php

namespace App\Http\Requests;

use App\Support\ReadinessFormSupport;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCoachReadinessFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ReadinessFormSupport::fieldsValidationRules();
    }
}
