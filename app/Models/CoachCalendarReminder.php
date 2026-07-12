<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachCalendarReminder extends Model
{
    protected $fillable = [
        'coach_id',
        'athlete_id',
        'title',
        'event_date',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
