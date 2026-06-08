<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionFeedbackReply extends Model
{
    protected $fillable = [
        'session_feedback_id',
        'coach_id',
        'body',
    ];

    public function feedback(): BelongsTo
    {
        return $this->belongsTo(SessionFeedback::class, 'session_feedback_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function audioFiles(): HasMany
    {
        return $this->hasMany(SessionFeedbackMedia::class)
            ->where('kind', SessionFeedbackMedia::KIND_AUDIO)
            ->orderBy('sort_order');
    }
}
