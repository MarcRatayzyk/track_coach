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
            'videos' => ['required', 'array', 'min:1', 'max:3'],
            'videos.*' => [
                'required',
                'file',
                'mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo',
                'max:102400',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'videos.required' => 'Ajoutez au moins une vidéo.',
            'videos.max' => 'Vous pouvez envoyer au maximum 3 vidéos.',
            'videos.*.max' => 'Chaque vidéo ne doit pas dépasser 100 Mo.',
        ];
    }
}
