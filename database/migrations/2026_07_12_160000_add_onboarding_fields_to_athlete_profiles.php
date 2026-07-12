<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('athlete_profiles', function (Blueprint $table): void {
            $table->string('profession')->nullable()->after('bio');
            $table->unsignedSmallInteger('years_training')->nullable()->after('profession');
        });
    }

    public function down(): void
    {
        Schema::table('athlete_profiles', function (Blueprint $table): void {
            $table->dropColumn(['profession', 'years_training']);
        });
    }
};
