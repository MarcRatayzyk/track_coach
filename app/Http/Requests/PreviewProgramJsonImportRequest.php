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
                    $validator->errors()->add('json', 'JSON trop volumineux (max ~2 Mo).');
                }

                return;
            }

            if (is_array($json)) {
                $encoded = json_encode($json);
                if ($encoded !== false && strlen($encoded) > 2_000_000) {
                    $validator->errors()->add('json', 'JSON trop volumineux (max ~2 Mo).');
                }

                return;
            }

            $validator->errors()->add('json', 'Fournis une chaîne JSON ou un objet.');
        });
    }

    public function messages(): array
    {
        return [
            'json.required' => 'Colle le JSON du programme.',
        ];
    }
}
