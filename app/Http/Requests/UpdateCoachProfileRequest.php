<?php

namespace App\Http\Requests;

use App\Support\IpfWeightCategorySupport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCoachProfileRequest extends FormRequest
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
        return [
            'bio' => ['nullable', 'string', 'max:5000'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', Rule::in(IpfWeightCategorySupport::specialtyOptions())],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'certifications' => ['nullable', 'string', 'max:2000'],
            'club_gym' => ['nullable', 'string', 'max:120'],
        ];
    }
}
