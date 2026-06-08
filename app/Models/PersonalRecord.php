<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalRecord extends Model
{
    protected $fillable = [
        'athlete_id',
        'squat',
        'bench',
        'deadlift',
        'reference_date',
    ];

    protected $casts = [
        'reference_date' => 'date',
    ];

    public function athlete()
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
