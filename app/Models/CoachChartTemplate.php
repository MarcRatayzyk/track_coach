<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoachChartTemplate extends Model
{
    protected $fillable = [
        'coach_id',
        'name',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function dashboardItems(): HasMany
    {
        return $this->hasMany(CoachStatsDashboardItem::class, 'template_id');
    }
}
