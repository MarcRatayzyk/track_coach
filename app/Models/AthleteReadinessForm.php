<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AthleteReadinessForm extends Model
{
    protected $fillable = [
        'athlete_id',
        'fields',
    ];

    protected $casts = [
        'fields' => 'array',
    ];

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
