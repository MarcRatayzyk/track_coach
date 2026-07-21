<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyCoachEmailNotification;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens;
    use CanResetPassword;
    use HasFactory;
    use MustVerifyEmail;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'initial_setup_completed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'initial_setup_completed_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    public function athletes(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'coach_athlete', 'coach_id', 'athlete_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'coach_athlete', 'athlete_id', 'coach_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function profile(): HasOne
    {
        return $this->hasOne(AthleteProfile::class, 'user_id');
    }

    public function coachProfile(): HasOne
    {
        return $this->hasOne(CoachProfile::class, 'user_id');
    }

    public function calendarReminders(): HasMany
    {
        return $this->hasMany(CoachCalendarReminder::class, 'coach_id');
    }

    public function primaryCoach(): ?User
    {
        return $this->coaches()
            ->where('users.role', 'coach')
            ->wherePivot('status', 'active')
            ->orderBy('coach_athlete.created_at')
            ->first();
    }

    public function personalRecords(): HasMany
    {
        return $this->hasMany(PersonalRecord::class, 'athlete_id');
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'athlete_id');
    }

    public function latestPr(): HasOne
    {
        return $this->hasOne(PersonalRecord::class, 'athlete_id')->latestOfMany('reference_date');
    }

    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class, 'athlete_id');
    }

    public function upcomingCompetition(): HasOne
    {
        return $this->hasOne(Competition::class, 'athlete_id')
            ->whereDate('competition_date', '>=', now())
            ->oldestOfMany('competition_date');
    }

    public function programAssignments(): HasMany
    {
        return $this->hasMany(AthleteProgramAssignment::class, 'athlete_id');
    }

    public function sessionFeedbacksAsAthlete(): HasMany
    {
        return $this->hasMany(SessionFeedback::class, 'athlete_id');
    }

    public function sessionFeedbacksAsCoach(): HasMany
    {
        return $this->hasMany(SessionFeedback::class, 'coach_id');
    }

    public function readinessEntries(): HasMany
    {
        return $this->hasMany(AthleteReadinessEntry::class, 'athlete_id');
    }

    public function coachReadinessForm(): HasOne
    {
        return $this->hasOne(CoachReadinessForm::class, 'coach_id');
    }

    public function athleteReadinessForm(): HasOne
    {
        return $this->hasOne(AthleteReadinessForm::class, 'athlete_id');
    }

    public function dayTableLayouts(): HasMany
    {
        return $this->hasMany(DayTableLayout::class, 'coach_id');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyCoachEmailNotification);
    }
}
