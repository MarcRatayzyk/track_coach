<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_training_days', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('session_label');
        });
    }

    public function down(): void
    {
        Schema::table('program_training_days', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
