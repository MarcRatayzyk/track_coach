<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewProgramImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        $maxKb = (int) ceil(max(
            (int) config('program_import.max_csv_bytes', 5 * 1024 * 1024),
            (int) config('program_import.max_photo_bytes', 8 * 1024 * 1024),
        ) / 1024);

        return [
            'file' => ['required', 'file', 'max:'.$maxKb],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $file = $this->file('file');
            if ($file === null) {
                return;
            }

            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($ext, ['csv', 'txt', 'xlsx', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'], true)) {
                $validator->errors()->add(
                    'file',
                    __('messages.validation.file_mimes_import'),
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'file.required' => __('messages.validation.file_required_program'),
            'file.max' => __('messages.validation.file_too_large'),
        ];
    }
}
