<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AthleteReadinessEntry extends Model
{
    public const MIN_SCORE = 1;

    public const MAX_SCORE = 10;

    protected $fillable = [
        'athlete_id',
        'entry_date',
        'score',
        'sleep_score',
        'stress_score',
        'motivation_score',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'score' => 'integer',
        'sleep_score' => 'integer',
        'stress_score' => 'integer',
        'motivation_score' => 'integer',
    ];

    public static function computeScore(int $sleep, int $stress, int $motivation): int
    {
        return (int) round(($sleep + $stress + $motivation) / 3);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
