<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewProgramImportPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        $maxKb = (int) ceil(((int) config('program_import.max_photo_bytes', 8 * 1024 * 1024)) / 1024);

        return [
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,gif,pdf',
                "max:{$maxKb}",
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => __('messages.validation.file_required_photo'),
            'file.mimes' => __('messages.validation.file_mimes_photo'),
            'file.max' => __('messages.validation.file_too_large'),
        ];
    }
}
