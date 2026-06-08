<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('athlete');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('coach_athlete', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['coach_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_athlete');
        Schema::dropIfExists('users');
    }
};
