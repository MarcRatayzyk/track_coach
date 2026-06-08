<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramDayExercise extends Model
{
    public const SECTION_TOPSET = 'topset';

    public const SECTION_BACKOFF = 'backoff';

    public const SECTION_ACCESSORY = 'accessory';

    protected $fillable = [
        'training_day_id',
        'block_index',
        'lift',
        'exercise_variant_id',
        'section',
        'exercise_name',
        'sets',
        'reps',
        'load',
        'load_percent',
        'rpe',
        'rest_seconds',
        'sort_order',
    ];

    protected $casts = [
        'rpe' => 'float',
        'load_percent' => 'float',
        'rest_seconds' => 'integer',
    ];

    public function trainingDay(): BelongsTo
    {
        return $this->belongsTo(ProgramTrainingDay::class, 'training_day_id');
    }

    public function exerciseVariant(): BelongsTo
    {
        return $this->belongsTo(ExerciseVariant::class);
    }
}
