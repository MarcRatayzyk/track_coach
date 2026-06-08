<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_day_exercises', function (Blueprint $table): void {
            $table->unsignedSmallInteger('block_index')->default(0)->after('training_day_id');
            $table->string('lift')->nullable()->after('block_index');
        });
    }

    public function down(): void
    {
        Schema::table('program_day_exercises', function (Blueprint $table): void {
            $table->dropColumn(['block_index', 'lift']);
        });
    }
};
