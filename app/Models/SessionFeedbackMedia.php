<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class SessionFeedbackMedia extends Model
{
    public const KIND_VIDEO = 'video';

    public const KIND_AUDIO = 'audio';

    public const STATUS_PENDING = 'pending';

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_ATTACHED = 'attached';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'session_feedback_id',
        'uploaded_by',
        'program_day_exercise_id',
        'series_info',
        'kind',
        'disk',
        'path',
        'mime_type',
        'original_name',
        'size_bytes',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'series_info' => 'array',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(SessionFeedback::class, 'session_feedback_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(SessionFeedbackAnnotation::class, 'session_feedback_media_id')
            ->orderBy('timestamp_ms');
    }

    public function url(): string
    {
        $disk = Storage::disk($this->disk);

        if ($this->disk === 's3' || config("filesystems.disks.{$this->disk}.driver") === 's3') {
            return $disk->temporaryUrl($this->path, now()->addHour());
        }

        return $disk->url($this->path);
    }
}
