<?php

namespace App\Http\Requests;

use App\Models\SessionFeedback;
use App\Models\SessionFeedbackMedia;
use App\Support\VideoUploadDisk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSessionFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SessionFeedback::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'athlete_notes' => ['nullable', 'string', 'max:10000'],
        ];

        if (VideoUploadDisk::usesDirectUpload()) {
            $rules['video_upload_ids'] = ['nullable', 'array', 'max:'.VideoUploadDisk::MAX_FILES];
            $rules['video_upload_ids.*'] = ['integer', 'distinct'];
        } else {
            $rules['videos'] = ['nullable', 'array', 'max:'.VideoUploadDisk::MAX_FILES];
            $rules['videos.*'] = [
                'required',
                'file',
                'mimetypes:video/mp4,video/webm,video/quicktime,video/x-msvideo,video/3gpp,video/3gpp2,video/x-matroska,video/x-m4v',
                'max:102400',
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $notes = trim((string) $this->input('athlete_notes', ''));

            if (VideoUploadDisk::usesDirectUpload()) {
                $ids = array_values(array_map('intval', (array) $this->input('video_upload_ids', [])));
                $videoCount = count($ids);

                if ($notes === '' && $videoCount === 0) {
                    $validator->errors()->add(
                        'athlete_notes',
                        'Ajoutez un message ou au moins une vidéo.',
                    );
                }

                if ($videoCount === 0) {
                    return;
                }

                $owned = SessionFeedbackMedia::query()
                    ->where('uploaded_by', $this->user()->id)
                    ->where('kind', SessionFeedbackMedia::KIND_VIDEO)
                    ->where('status', SessionFeedbackMedia::STATUS_UPLOADED)
                    ->whereNull('session_feedback_id')
                    ->whereIn('id', $ids)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $missing = array_diff($ids, $owned);
                if ($missing !== []) {
                    $validator->errors()->add(
                        'video_upload_ids',
                        'Une ou plusieurs vidéos sont invalides ou non finalisées.',
                    );
                }

                return;
            }

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
            'videos.max' => 'Vous pouvez envoyer au maximum '.VideoUploadDisk::MAX_FILES.' vidéos.',
            'videos.*.max' => 'Chaque vidéo ne doit pas dépasser 100 Mo.',
            'videos.*.mimetypes' => 'Format vidéo non pris en charge (MP4, MOV, WebM, 3GP…).',
            'video_upload_ids.max' => 'Vous pouvez envoyer au maximum '.VideoUploadDisk::MAX_FILES.' vidéos.',
        ];
    }
}
