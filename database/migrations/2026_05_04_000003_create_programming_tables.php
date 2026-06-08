<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('goal')->nullable();
            $table->string('level')->default('intermediate');
            $table->timestamps();
        });

        Schema::create('program_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('program_templates')->cascadeOnDelete();
            $table->unsignedInteger('week');
            $table->string('focus')->nullable();
            $table->timestamps();
        });

        Schema::create('program_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('block_id')->constrained('program_blocks')->cascadeOnDelete();
            $table->unsignedTinyInteger('day');
            $table->string('exercise');
            $table->unsignedTinyInteger('sets')->default(1);
            $table->unsignedTinyInteger('reps')->default(1);
            $table->decimal('rpe', 3, 1)->nullable();
            $table->unsignedInteger('load')->nullable();
            $table->timestamps();
        });

        Schema::create('athlete_program_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('program_templates')->cascadeOnDelete();
            $table->date('date_start');
            $table->date('date_end')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_program_assignments');
        Schema::dropIfExists('program_sessions');
        Schema::dropIfExists('program_blocks');
        Schema::dropIfExists('program_templates');
    }
};
