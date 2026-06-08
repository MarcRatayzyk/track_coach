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
    ];

    protected $casts = [
        'session_date' => 'date',
        'items' => 'array',
    ];

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
