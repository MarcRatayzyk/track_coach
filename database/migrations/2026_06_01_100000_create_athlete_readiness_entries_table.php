<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athlete_readiness_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->date('entry_date');
            $table->unsignedTinyInteger('score');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['athlete_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_readiness_entries');
    }
};
