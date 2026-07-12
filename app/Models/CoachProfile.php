<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'avatar_path',
        'specialties',
        'years_experience',
        'certifications',
        'club_gym',
    ];

    protected $casts = [
        'specialties' => 'array',
        'years_experience' => 'integer',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
