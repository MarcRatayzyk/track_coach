<?php

namespace App\Http\Requests;

use App\Models\SessionFeedback;
use App\Models\SessionFeedbackMedia;
use App\Support\FeedbackFrequencySupport;
use App\Support\SessionFeedbackPresenter;
use App\Support\VideoUploadDisk;
use Carbon\Carbon;
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
            'video_series' => ['nullable', 'array'],
            'video_series.*' => ['nullable', 'integer'],
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
            $hasLoggedNotes = $this->hasLoggedSessionNotes();

            if (VideoUploadDisk::usesDirectUpload()) {
                $ids = array_values(array_map('intval', (array) $this->input('video_upload_ids', [])));
                $videoCount = count($ids);

                if ($notes === '' && $videoCount === 0 && ! $hasLoggedNotes) {
                    $validator->errors()->add(
                        'athlete_notes',
                        __('messages.validation.feedback_content_or_video'),
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
                        __('messages.validation.videos_invalid'),
                    );
                }

                return;
            }

            $videoCount = count($this->file('videos', []));

            if ($notes === '' && $videoCount === 0 && ! $hasLoggedNotes) {
                $validator->errors()->add(
                    'athlete_notes',
                    __('messages.validation.feedback_content_or_video'),
                );
            }
        });
    }

    private function hasLoggedSessionNotes(): bool
    {
        $user = $this->user();
        $sessionDate = $this->input('session_date');
        if ($user === null || ! is_string($sessionDate) || $sessionDate === '') {
            return false;
        }

        try {
            $date = Carbon::parse($sessionDate)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        if (FeedbackFrequencySupport::isWeekly($user)) {
            [$weekStart, $weekEnd] = FeedbackFrequencySupport::weekBounds($date);
            $notes = SessionFeedbackPresenter::loggedNotesForAthleteBetween(
                $user->id,
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            );
        } else {
            $dateString = $date->toDateString();
            $notes = SessionFeedbackPresenter::loggedNotesForAthleteBetween(
                $user->id,
                $dateString,
                $dateString,
            );
        }

        return $notes !== [];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'videos.max' => __('messages.validation.videos_max', ['max' => VideoUploadDisk::MAX_FILES]),
            'videos.*.max' => __('messages.validation.video_max_size'),
            'videos.*.mimetypes' => __('messages.validation.video_mimetypes'),
            'video_upload_ids.max' => __('messages.validation.videos_max', ['max' => VideoUploadDisk::MAX_FILES]),
        ];
    }
}
