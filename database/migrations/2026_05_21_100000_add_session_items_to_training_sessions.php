<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->string('session_label')->nullable()->after('session_date');
            $table->string('main_lift')->default('squat')->after('session_label');
            $table->json('items')->nullable()->after('deadlift');
        });
    }

    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->dropColumn(['session_label', 'main_lift', 'items']);
        });
    }
};
