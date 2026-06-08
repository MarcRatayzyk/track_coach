<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionFeedbackReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $feedback = $this->route('feedback');

        return $feedback !== null && $this->user()?->can('reply', $feedback);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:10000'],
            'audio_files' => ['nullable', 'array', 'max:5'],
            'audio_files.*' => [
                'required',
                'file',
                'mimetypes:audio/mpeg,audio/mp4,audio/webm,audio/ogg,audio/x-m4a,video/webm',
                'max:15360',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $body = trim((string) $this->input('body', ''));
            $audioCount = count($this->file('audio_files', []));

            if ($body === '' && $audioCount === 0) {
                $validator->errors()->add('body', 'Ajoutez un message texte ou au moins un fichier audio.');
            }
        });
    }
}
