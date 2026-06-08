<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramWeek extends Model
{
    public const BLOCK_VOLUME = 'volume';

    public const BLOCK_INTENSIFICATION = 'intensification';

    public const BLOCK_PEAKING = 'peaking';

    protected $fillable = [
        'template_id',
        'week_number',
        'block_type',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProgramTemplate::class, 'template_id');
    }

    public function trainingDays(): HasMany
    {
        return $this->hasMany(ProgramTrainingDay::class, 'week_id')->orderBy('day_number');
    }
}
