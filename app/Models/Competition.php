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
    ];

    protected $casts = [
        'competition_date' => 'date',
        'match_plan_data' => 'array',
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
