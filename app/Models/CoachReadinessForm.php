<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachReadinessForm extends Model
{
    protected $fillable = [
        'coach_id',
        'fields',
    ];

    protected $casts = [
        'fields' => 'array',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
