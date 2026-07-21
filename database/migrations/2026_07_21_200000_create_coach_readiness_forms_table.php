<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_readiness_forms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('fields');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_readiness_forms');
    }
};
