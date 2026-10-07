<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
        'parent_exercise_id',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_exercise_id');
    }

    /**
     * Catégorie d'accessoire déjà présente au catalogue
     * (jambes, dos, triceps, épaules, rowing).
     * Les pectoraux n'ont pas de carte dédiée : ils partent dans Épaules,
     * et le coach peut changer la catégorie à l'édition.
     */
    public static function inferAccessoryGroupSlug(?string $lift, ?string $movementPattern): string
    {
        $muscle = mb_strtolower(Str::ascii((string) $movementPattern));

        if (str_contains($muscle, 'triceps')) {
            return 'triceps';
        }

        if (str_contains($muscle, 'epaule') || str_contains($muscle, 'shoulder')) {
            return 'epaules';
        }

        if (str_contains($muscle, 'dos') || str_contains($muscle, 'back') || str_contains($muscle, 'biceps')) {
            return 'dos-accessoire';
        }

        if (
            str_contains($muscle, 'quadri')
            || str_contains($muscle, 'ischio')
            || str_contains($muscle, 'fessier')
            || str_contains($muscle, 'mollet')
            || str_contains($muscle, 'glute')
            || str_contains($muscle, 'hamstring')
            || str_contains($muscle, 'calf')
        ) {
            return 'jambes-accessoire';
        }

        return match ($lift) {
            self::LIFT_SQUAT => 'jambes-accessoire',
            self::LIFT_DEADLIFT => 'dos-accessoire',
            self::LIFT_BENCH => 'epaules',
            default => 'rowing-haltere',
        };
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
