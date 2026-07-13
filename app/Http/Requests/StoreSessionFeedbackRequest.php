<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSessionFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\SessionFeedback::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'athlete_notes' => ['nullable', 'string', 'max:10000'],
            'videos' => ['nullable', 'array', 'max:3'],
            'videos.*' => [
                'required',
                'file',
                'mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo,video/3gpp,video/3gpp2,video/x-matroska,video/x-m4v',
                'max:102400',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $notes = trim((string) $this->input('athlete_notes', ''));
            $videoCount = count($this->file('videos', []));

            if ($notes === '' && $videoCount === 0) {
                $validator->errors()->add(
                    'athlete_notes',
                    'Ajoutez un message ou au moins une vidéo.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'videos.max' => 'Vous pouvez envoyer au maximum 3 vidéos.',
            'videos.*.max' => 'Chaque vidéo ne doit pas dépasser 100 Mo.',
            'videos.*.mimetypes' => 'Format vidéo non pris en charge (MP4, MOV, WebM, 3GP…).',
        ];
    }
}
