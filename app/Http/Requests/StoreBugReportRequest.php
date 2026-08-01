<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBugReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', Rule::in(['bug', 'fix', 'idea', 'other'])],
            'description' => ['required', 'string', 'max:5000'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'screenshot' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est obligatoire.',
            'category.required' => 'Choisis une catégorie.',
            'category.in' => 'Catégorie invalide.',
            'description.required' => 'La description est obligatoire.',
            'description.max' => 'La description ne peut pas dépasser :max caractères.',
            'screenshot.image' => 'La capture doit être une image.',
            'screenshot.mimes' => 'Formats acceptés : JPEG, PNG ou WebP.',
            'screenshot.max' => 'La capture ne doit pas dépasser 4 Mo.',
        ];
    }
}
