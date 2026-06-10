<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athlete_body_weight_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->date('entry_date');
            $table->decimal('weight_kg', 5, 2);
            $table->timestamps();

            $table->unique(['athlete_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_body_weight_entries');
    }
};
