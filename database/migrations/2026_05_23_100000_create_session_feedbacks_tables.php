<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_feedbacks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('athlete_program_assignment_id')->constrained('athlete_program_assignments')->cascadeOnDelete();
            $table->foreignId('program_training_day_id')->constrained('program_training_days')->cascadeOnDelete();
            $table->date('session_date');
            $table->text('athlete_notes')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(
                ['athlete_id', 'program_training_day_id', 'session_date'],
                'session_feedbacks_athlete_day_date_unique',
            );
            $table->index(['coach_id', 'status', 'submitted_at']);
            $table->index(['athlete_id', 'session_date']);
        });

        Schema::create('session_feedback_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_feedback_id')->constrained('session_feedbacks')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->timestamps();

            $table->unique('session_feedback_id');
        });

        Schema::create('session_feedback_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_feedback_id')->constrained('session_feedbacks')->cascadeOnDelete();
            $table->foreignId('session_feedback_reply_id')->nullable()->constrained('session_feedback_replies')->cascadeOnDelete();
            $table->string('kind');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['session_feedback_id', 'kind']);
        });

        Schema::table('dashboard_tasks', function (Blueprint $table): void {
            $table->foreignId('session_feedback_id')
                ->nullable()
                ->after('athlete_id')
                ->constrained('session_feedbacks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('session_feedback_id');
        });

        Schema::dropIfExists('session_feedback_media');
        Schema::dropIfExists('session_feedback_replies');
        Schema::dropIfExists('session_feedbacks');
    }
};
