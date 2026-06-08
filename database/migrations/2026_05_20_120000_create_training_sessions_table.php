<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->date('session_date');
            $table->unsignedInteger('squat')->default(0);
            $table->unsignedInteger('bench')->default(0);
            $table->unsignedInteger('deadlift')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['athlete_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
