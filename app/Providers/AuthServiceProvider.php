<?php

namespace App\Providers;

use App\Models\AthleteProgramAssignment;
use App\Models\DashboardTask;
use App\Models\MessageThread;
use App\Models\ProgramTemplate;
use App\Models\SessionFeedback;
use App\Models\User;
use App\Policies\AthletePolicy;
use App\Policies\AthleteProgramAssignmentPolicy;
use App\Policies\DashboardTaskPolicy;
use App\Policies\MessageThreadPolicy;
use App\Policies\ProgramTemplatePolicy;
use App\Policies\SessionFeedbackPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => AthletePolicy::class,
        ProgramTemplate::class => ProgramTemplatePolicy::class,
        AthleteProgramAssignment::class => AthleteProgramAssignmentPolicy::class,
        MessageThread::class => MessageThreadPolicy::class,
        DashboardTask::class => DashboardTaskPolicy::class,
        SessionFeedback::class => SessionFeedbackPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
