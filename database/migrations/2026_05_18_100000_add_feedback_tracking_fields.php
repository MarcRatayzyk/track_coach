<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('athlete_profiles', function (Blueprint $table): void {
            $table->string('feedback_frequency')->default('weekly')->after('bio');
        });

        Schema::table('dashboard_tasks', function (Blueprint $table): void {
            $table->date('session_date')->nullable()->after('type');
            $table->date('period_week_start')->nullable()->after('session_date');
            $table->timestamp('completed_at')->nullable()->after('status');

            $table->unique(
                ['coach_id', 'athlete_id', 'type', 'session_date'],
                'dashboard_tasks_daily_feedback_unique'
            );

            $table->unique(
                ['coach_id', 'athlete_id', 'type', 'period_week_start'],
                'dashboard_tasks_weekly_feedback_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_tasks', function (Blueprint $table): void {
            $table->dropUnique('dashboard_tasks_weekly_feedback_unique');
            $table->dropUnique('dashboard_tasks_daily_feedback_unique');
            $table->dropColumn(['session_date', 'period_week_start', 'completed_at']);
        });

        Schema::table('athlete_profiles', function (Blueprint $table): void {
            $table->dropColumn('feedback_frequency');
        });
    }
};
