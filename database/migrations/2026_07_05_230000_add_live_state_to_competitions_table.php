<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table): void {
            $table->json('live_state')->nullable()->after('match_plan_data');
            $table->timestamp('live_started_at')->nullable()->after('live_state');
            $table->timestamp('live_ended_at')->nullable()->after('live_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table): void {
            $table->dropColumn(['live_state', 'live_started_at', 'live_ended_at']);
        });
    }
};
