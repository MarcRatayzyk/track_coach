<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardTask extends Model
{
    public const TYPE_FEEDBACK_SESSION = 'feedback_session';

    protected $fillable = [
        'coach_id',
        'athlete_id',
        'session_feedback_id',
        'type',
        'session_date',
        'period_week_start',
        'due_at',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'period_week_start' => 'date',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function isDaily(): bool
    {
        return $this->session_date !== null && $this->period_week_start === null;
    }

    public function isWeekly(): bool
    {
        return $this->period_week_start !== null;
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function sessionFeedback(): BelongsTo
    {
        return $this->belongsTo(SessionFeedback::class);
    }
}
