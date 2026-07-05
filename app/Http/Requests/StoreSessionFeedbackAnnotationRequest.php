<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionFeedbackAnnotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $media = $this->route('media');

        if ($media === null) {
            return false;
        }

        $feedback = $media->feedback;

        return $feedback !== null && $this->user()?->can('annotate', $feedback);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'timestamp_ms' => ['required', 'integer', 'min:0'],
            'body' => ['nullable', 'string', 'max:2000'],
            'shapes' => ['nullable', 'array'],
            'shapes.*.type' => ['required_with:shapes', 'in:line,circle,arrow'],
            'shapes.*.x1' => ['required_with:shapes', 'numeric', 'min:0', 'max:1'],
            'shapes.*.y1' => ['required_with:shapes', 'numeric', 'min:0', 'max:1'],
            'shapes.*.x2' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'shapes.*.y2' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'shapes.*.r' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ];
    }
}
