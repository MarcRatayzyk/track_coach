<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingSession extends Model
{
    protected $fillable = [
        'athlete_id',
        'session_date',
        'session_label',
        'main_lift',
        'items',
        'squat',
        'bench',
        'deadlift',
        'notes',
        'timer_started_at',
        'timer_elapsed_seconds',
        'finished_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'items' => 'array',
        'timer_started_at' => 'datetime',
        'timer_elapsed_seconds' => 'integer',
        'finished_at' => 'datetime',
    ];

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
