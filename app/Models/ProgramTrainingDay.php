<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramTrainingDay extends Model
{
    public const LIFT_SQUAT = 'squat';

    public const LIFT_BENCH = 'bench';

    public const LIFT_DEADLIFT = 'deadlift';

    protected $fillable = [
        'week_id',
        'day_number',
        'main_lift',
        'session_label',
    ];

    public function week(): BelongsTo
    {
        return $this->belongsTo(ProgramWeek::class, 'week_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(ProgramDayExercise::class, 'training_day_id')->orderBy('sort_order');
    }
}
