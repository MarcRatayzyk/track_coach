<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AthleteProgramAssignment extends Model
{
    protected $fillable = [
        'athlete_id',
        'template_id',
        'date_start',
        'date_end',
        'status',
        'archived_at',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'archived_at' => 'datetime',
    ];

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProgramTemplate::class, 'template_id');
    }
}
