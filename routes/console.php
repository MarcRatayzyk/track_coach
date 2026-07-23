<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('programs:archive-completed')->daily();
Schedule::command('feedbacks:cleanup-orphan-uploads')->hourly();
Schedule::command('backup:run')->dailyAt('03:00')->withoutOverlapping();
