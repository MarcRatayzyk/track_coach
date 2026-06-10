<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AthleteBodyWeightEntry extends Model
{
    public const MIN_WEIGHT_KG = 30;

    public const MAX_WEIGHT_KG = 250;

    protected $fillable = [
        'athlete_id',
        'entry_date',
        'weight_kg',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'weight_kg' => 'decimal:2',
    ];

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
