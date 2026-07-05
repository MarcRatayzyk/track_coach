<?php

namespace App\Models;

use App\Support\MatchPlanData;
use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    protected $fillable = [
        'athlete_id',
        'name',
        'competition_date',
        'goal',
        'location',
        'match_plan',
        'match_plan_data',
        'live_state',
        'live_started_at',
        'live_ended_at',
    ];

    protected $casts = [
        'competition_date' => 'date',
        'match_plan_data' => 'array',
        'live_state' => 'array',
        'live_started_at' => 'datetime',
        'live_ended_at' => 'datetime',
    ];

    public function athlete()
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function hasMatchPlan(): bool
    {
        return MatchPlanData::hasContent($this->match_plan_data, $this->match_plan);
    }
}
