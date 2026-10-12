<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->timestamp('timer_started_at')->nullable()->after('notes');
            $table->unsignedInteger('timer_elapsed_seconds')->default(0)->after('timer_started_at');
            $table->timestamp('finished_at')->nullable()->after('timer_elapsed_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->dropColumn(['timer_started_at', 'timer_elapsed_seconds', 'finished_at']);
        });
    }
};
