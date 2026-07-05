<?php

namespace App\Http\Requests;

use App\Models\SessionFeedback;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'max:5000'],
            'session_feedback_id' => ['nullable', 'integer', 'exists:session_feedbacks,id'],
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
            $content = trim((string) $this->input('content', ''));
            $audioCount = count($this->file('audio_files', []));
            $feedbackId = $this->input('session_feedback_id');

            if ($feedbackId === null && $content === '' && $audioCount === 0) {
                $validator->errors()->add('content', 'Le message ne peut pas être vide.');
            }

            if ($feedbackId !== null && $content === '' && $audioCount === 0) {
                $validator->errors()->add('content', 'Ajoutez un message texte ou au moins un fichier audio.');
            }

            if ($feedbackId !== null) {
                $feedback = SessionFeedback::query()->find($feedbackId);
                if ($feedback !== null && ! $this->user()?->can('reply', $feedback)) {
                    $validator->errors()->add('session_feedback_id', 'Vous ne pouvez pas répondre à ce retour.');
                }
            }
        });
    }
}
