<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'coach_id',
        'is_custom',
        'name',
        'slug',
        'lift',
        'category',
        'equipment',
        'movement_pattern',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ExerciseVariant::class);
    }

    public function scopeForCoach(Builder $query, User $coach): Builder
    {
        return $query->where(function (Builder $builder) use ($coach): void {
            $builder->whereNull('coach_id')
                ->orWhere('coach_id', $coach->id);
        });
    }
}
