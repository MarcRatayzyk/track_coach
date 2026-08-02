<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewProgramJsonImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        return [
            // Chaîne JSON ou objet déjà décodé par Laravel
            'json' => ['required'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $json = $this->input('json');
            if (is_string($json)) {
                if (strlen($json) > 2_000_000) {
                    $validator->errors()->add('json', __('messages.validation.json_too_large'));
                }

                return;
            }

            if (is_array($json)) {
                $encoded = json_encode($json);
                if ($encoded !== false && strlen($encoded) > 2_000_000) {
                    $validator->errors()->add('json', __('messages.validation.json_too_large'));
                }

                return;
            }

            $validator->errors()->add('json', __('messages.validation.json_invalid'));
        });
    }

    public function messages(): array
    {
        return [
            'json.required' => __('messages.validation.json_required'),
        ];
    }
}
