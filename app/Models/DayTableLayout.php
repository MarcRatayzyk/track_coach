<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayTableLayout extends Model
{
    public const EXERCISE_MODE_NAME = 'name';

    public const EXERCISE_MODE_SPLIT_LIFT = 'split_lift';

    public const LOAD_MODE_KG = 'kg';

    public const LOAD_MODE_PERCENT = 'percent';

    public const LOAD_MODE_RPE = 'rpe';

    protected $fillable = [
        'coach_id',
        'name',
        'columns',
        'exercise_mode',
        'load_mode',
        'is_default',
    ];

    protected $casts = [
        'columns' => 'array',
        'is_default' => 'boolean',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'columns' => $this->columns ?? [],
            'exercise_mode' => $this->exercise_mode,
            'load_mode' => $this->load_mode,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function classicSnapshot(): array
    {
        return [
            'columns' => ['section', 'sets', 'reps', 'load'],
            'exercise_mode' => self::EXERCISE_MODE_NAME,
            'load_mode' => self::LOAD_MODE_KG,
        ];
    }
}
