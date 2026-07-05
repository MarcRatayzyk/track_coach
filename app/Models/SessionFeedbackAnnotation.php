<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionFeedbackAnnotation extends Model
{
    protected $fillable = [
        'session_feedback_media_id',
        'coach_id',
        'timestamp_ms',
        'body',
        'shapes',
    ];

    protected $casts = [
        'timestamp_ms' => 'integer',
        'shapes' => 'array',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(SessionFeedbackMedia::class, 'session_feedback_media_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
