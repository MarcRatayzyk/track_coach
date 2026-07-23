<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('session_feedback_media', 'program_day_exercise_id')) {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->foreignId('program_day_exercise_id')
                    ->nullable()
                    ->after('session_feedback_reply_id')
                    ->constrained('program_day_exercises')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('session_feedback_media', 'series_info')) {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->json('series_info')->nullable()->after('program_day_exercise_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('session_feedback_media', 'program_day_exercise_id')) {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('program_day_exercise_id');
            });
        }

        if (Schema::hasColumn('session_feedback_media', 'series_info')) {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->dropColumn('series_info');
            });
        }
    }
};
