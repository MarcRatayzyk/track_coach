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

    protected $fillable = [
        'session_feedback_id',
        'kind',
        'disk',
        'path',
        'mime_type',
        'original_name',
        'size_bytes',
        'sort_order',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(SessionFeedback::class, 'session_feedback_id');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(SessionFeedbackAnnotation::class, 'session_feedback_media_id')
            ->orderBy('timestamp_ms');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
