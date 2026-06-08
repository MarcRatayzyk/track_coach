<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_training_days', function (Blueprint $table): void {
            $table->string('session_label')->nullable()->after('main_lift');
        });
    }

    public function down(): void
    {
        Schema::table('program_training_days', function (Blueprint $table): void {
            $table->dropColumn('session_label');
        });
    }
};
