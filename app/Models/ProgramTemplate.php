<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramTemplate extends Model
{
    protected $fillable = [
        'coach_id',
        'name',
        'goal',
        'level',
        'table_layout',
        'default_warmup_notes',
        'default_warmup_items',
    ];

    protected $casts = [
        'table_layout' => 'array',
        'default_warmup_items' => 'array',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(ProgramWeek::class, 'template_id')->orderBy('week_number');
    }
}
