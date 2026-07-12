<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AthleteProfile extends Model
{
    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const LEVEL_BEGINNER = 'beginner';

    public const LEVEL_INTERMEDIATE = 'intermediate';

    public const LEVEL_ADVANCED = 'advanced';

    public const LEVEL_ELITE = 'elite';

    protected $fillable = [
        'user_id',
        'birth_date',
        'height_cm',
        'sex',
        'weight_category',
        'level',
        'injuries_notes',
        'bio',
        'profession',
        'years_training',
        'feedback_frequency',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function athlete()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
