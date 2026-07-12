<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_calendar_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('athlete_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->date('event_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['coach_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_calendar_reminders');
    }
};
