<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewProgramImportMappedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    protected function prepareForValidation(): void
    {
        $mapping = $this->input('mapping');
        if (is_string($mapping)) {
            $decoded = json_decode($mapping, true);
            if (is_array($decoded)) {
                $this->merge(['mapping' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        $maxKb = (int) ceil(((int) config('program_import.max_csv_bytes', 2 * 1024 * 1024)) / 1024);

        return [
            'file' => ['required', 'file', 'max:'.$maxKb],
            'mapping' => ['required', 'array'],
            'mapping.week' => ['required', 'string'],
            'mapping.day' => ['required', 'string'],
            'mapping.exercise' => ['required', 'string'],
            'mapping.sets' => ['required', 'string'],
            'mapping.reps' => ['required', 'string'],
            'mapping.section' => ['nullable', 'string'],
            'mapping.load' => ['nullable', 'string'],
            'mapping.load_percent' => ['nullable', 'string'],
            'mapping.rpe' => ['nullable', 'string'],
            'mapping.rest_seconds' => ['nullable', 'string'],
            'mapping.main_lift' => ['nullable', 'string'],
            'mapping.session_label' => ['nullable', 'string'],
            'mapping.notes' => ['nullable', 'string'],
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
            if (! in_array($ext, ['csv', 'txt', 'xlsx'], true)) {
                $validator->errors()->add(
                    'file',
                    __('messages.validation.file_mimes_mapped'),
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'file.required' => __('messages.validation.file_required_csv'),
            'mapping.required' => __('messages.validation.mapping_required'),
        ];
    }
}
