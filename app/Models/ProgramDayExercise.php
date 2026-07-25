<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramDayExercise extends Model
{
    public const SECTION_TOPSET = 'topset';

    public const SECTION_BACKOFF = 'backoff';

    public const SECTION_ACCESSORY = 'accessory';

    public const SECTION_WARMUP = 'warmup';

    public const SCHEME_STANDARD = 'standard';

    public const SCHEME_RAMP = 'ramp';

    public const SCHEME_CLUSTER = 'cluster';

    public const SCHEMES = [
        self::SCHEME_STANDARD,
        self::SCHEME_RAMP,
        self::SCHEME_CLUSTER,
    ];

    protected $fillable = [
        'training_day_id',
        'block_index',
        'lift',
        'exercise_variant_id',
        'section',
        'set_scheme',
        'scheme_config',
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
        'load' => 'float',
        'rpe' => 'float',
        'load_percent' => 'float',
        'rest_seconds' => 'integer',
        'scheme_config' => 'array',
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
