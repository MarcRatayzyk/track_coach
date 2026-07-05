<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SessionFeedback extends Model
{
    protected $table = 'session_feedbacks';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_COACH_REPLIED = 'coach_replied';

    protected $fillable = [
        'coach_id',
        'athlete_id',
        'athlete_program_assignment_id',
        'program_training_day_id',
        'session_date',
        'athlete_notes',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(AthleteProgramAssignment::class, 'athlete_program_assignment_id');
    }

    public function programTrainingDay(): BelongsTo
    {
        return $this->belongsTo(ProgramTrainingDay::class, 'program_training_day_id');
    }

    public function athleteVideos(): HasMany
    {
        return $this->hasMany(SessionFeedbackMedia::class)
            ->where('kind', SessionFeedbackMedia::KIND_VIDEO)
            ->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(SessionFeedbackMedia::class)->orderBy('sort_order');
    }

    public function replyMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'session_feedback_id');
    }

    public function latestReplyMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'session_feedback_id')->latestOfMany();
    }

    public function dashboardTask(): HasOne
    {
        return $this->hasOne(DashboardTask::class, 'session_feedback_id');
    }

    public function isPendingCoachReply(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }
}
