<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athlete_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->date('birth_date')->nullable();
            $table->string('weight_class')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('squat')->default(0);
            $table->unsignedInteger('bench')->default(0);
            $table->unsignedInteger('deadlift')->default(0);
            $table->date('reference_date');
            $table->timestamps();
        });

        Schema::create('competitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->date('competition_date');
            $table->string('goal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
        Schema::dropIfExists('personal_records');
        Schema::dropIfExists('athlete_profiles');
    }
};
