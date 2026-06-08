<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_day_exercises', function (Blueprint $table): void {
            $table->decimal('load_percent', 5, 2)->nullable()->after('load');
        });
    }

    public function down(): void
    {
        Schema::table('program_day_exercises', function (Blueprint $table): void {
            $table->dropColumn('load_percent');
        });
    }
};
