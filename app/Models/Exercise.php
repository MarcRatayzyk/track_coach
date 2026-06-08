<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends Model
{
    public const LIFT_SQUAT = 'squat';

    public const LIFT_BENCH = 'bench';

    public const LIFT_DEADLIFT = 'deadlift';

    public const LIFT_GENERAL = 'general';

    public const CATEGORY_MAIN_LIFT = 'main_lift';

    public const CATEGORY_ACCESSORY = 'accessory';

    protected $fillable = [
        'name',
        'slug',
        'lift',
        'category',
        'equipment',
        'movement_pattern',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ExerciseVariant::class);
    }
}
