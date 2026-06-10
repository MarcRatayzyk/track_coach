<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE program_day_exercises ALTER COLUMN load TYPE DECIMAL(8,2) USING load::numeric(8,2)');

            return;
        }

        Schema::table('program_day_exercises', function (Blueprint $table): void {
            $table->decimal('load', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE program_day_exercises ALTER COLUMN load TYPE INTEGER USING ROUND(load)::integer');

            return;
        }

        Schema::table('program_day_exercises', function (Blueprint $table): void {
            $table->unsignedInteger('load')->nullable()->change();
        });
    }
};
