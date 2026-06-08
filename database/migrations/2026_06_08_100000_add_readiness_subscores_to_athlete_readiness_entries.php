<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('athlete_readiness_entries', function (Blueprint $table): void {
            $table->unsignedTinyInteger('sleep_score')->nullable();
            $table->unsignedTinyInteger('stress_score')->nullable();
            $table->unsignedTinyInteger('motivation_score')->nullable();
        });

        DB::table('athlete_readiness_entries')->update([
            'sleep_score' => DB::raw('score'),
            'stress_score' => DB::raw('score'),
            'motivation_score' => DB::raw('score'),
        ]);
    }

    public function down(): void
    {
        Schema::table('athlete_readiness_entries', function (Blueprint $table): void {
            $table->dropColumn(['sleep_score', 'stress_score', 'motivation_score']);
        });
    }
};
