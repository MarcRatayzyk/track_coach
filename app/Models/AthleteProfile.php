<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AthleteProfile extends Model
{
    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    protected $fillable = [
        'user_id',
        'birth_date',
        'weight_class',
        'bio',
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
