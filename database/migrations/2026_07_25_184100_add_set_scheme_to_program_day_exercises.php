<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_day_exercises', function (Blueprint $table): void {
            if (! Schema::hasColumn('program_day_exercises', 'set_scheme')) {
                $table->string('set_scheme', 20)->default('standard')->after('section');
            }
            if (! Schema::hasColumn('program_day_exercises', 'scheme_config')) {
                $table->json('scheme_config')->nullable()->after('set_scheme');
            }
        });
    }

    public function down(): void
    {
        Schema::table('program_day_exercises', function (Blueprint $table): void {
            if (Schema::hasColumn('program_day_exercises', 'scheme_config')) {
                $table->dropColumn('scheme_config');
            }
            if (Schema::hasColumn('program_day_exercises', 'set_scheme')) {
                $table->dropColumn('set_scheme');
            }
        });
    }
};
