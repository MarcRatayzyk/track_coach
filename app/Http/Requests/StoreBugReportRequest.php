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
            'title.required' => __('messages.validation.title_required'),
            'category.required' => __('messages.validation.category_required'),
            'category.in' => __('messages.validation.category_invalid'),
            'description.required' => __('messages.validation.description_required'),
            'description.max' => __('messages.validation.description_max'),
            'screenshot.image' => __('messages.validation.screenshot_image'),
            'screenshot.mimes' => __('messages.validation.screenshot_mimes'),
            'screenshot.max' => __('messages.validation.screenshot_max'),
        ];
    }
}
