<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_table_layouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->json('columns');
            $table->string('exercise_mode')->default('name');
            $table->string('load_mode')->default('kg');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('program_templates', function (Blueprint $table): void {
            $table->json('table_layout')->nullable()->after('level');
        });

        Schema::table('program_day_exercises', function (Blueprint $table): void {
            $table->unsignedSmallInteger('rest_seconds')->nullable()->after('rpe');
        });

        $this->seedDefaultLayoutsForExistingCoaches();
    }

    public function down(): void
    {
        Schema::table('program_day_exercises', function (Blueprint $table): void {
            $table->dropColumn('rest_seconds');
        });

        Schema::table('program_templates', function (Blueprint $table): void {
            $table->dropColumn('table_layout');
        });

        Schema::dropIfExists('day_table_layouts');
    }

    private function seedDefaultLayoutsForExistingCoaches(): void
    {
        $coachIds = DB::table('users')
            ->where('role', 'coach')
            ->pluck('id');

        $now = now();
        $defaultColumns = json_encode(['section', 'sets', 'reps', 'load']);

        foreach ($coachIds as $coachId) {
            DB::table('day_table_layouts')->insert([
                'coach_id' => $coachId,
                'name' => 'Classique',
                'columns' => $defaultColumns,
                'exercise_mode' => 'name',
                'load_mode' => 'kg',
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
