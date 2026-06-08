<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('lift')->default('general');
            $table->string('category')->default('accessory');
            $table->string('equipment')->default('barbell');
            $table->string('movement_pattern')->nullable();
            $table->timestamps();
        });

        Schema::create('exercise_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['exercise_id', 'slug']);
        });

        Schema::create('program_weeks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('program_templates')->cascadeOnDelete();
            $table->unsignedInteger('week_number');
            $table->string('block_type')->default('volume');
            $table->timestamps();

            $table->unique(['template_id', 'week_number']);
        });

        Schema::create('program_training_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('week_id')->constrained('program_weeks')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->string('main_lift')->default('squat');
            $table->timestamps();

            $table->unique(['week_id', 'day_number']);
        });

        Schema::create('program_day_exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_day_id')->constrained('program_training_days')->cascadeOnDelete();
            $table->foreignId('exercise_variant_id')->nullable()->constrained('exercise_variants')->nullOnDelete();
            $table->string('section');
            $table->string('exercise_name');
            $table->unsignedTinyInteger('sets')->default(1);
            $table->unsignedTinyInteger('reps')->default(1);
            $table->unsignedInteger('load')->nullable();
            $table->decimal('rpe', 3, 1)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $this->migrateLegacyProgramData();

        Schema::dropIfExists('program_sessions');
        Schema::dropIfExists('program_blocks');
    }

    public function down(): void
    {
        Schema::create('program_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('program_templates')->cascadeOnDelete();
            $table->unsignedInteger('week');
            $table->string('focus')->nullable();
            $table->timestamps();
        });

        Schema::create('program_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('block_id')->constrained('program_blocks')->cascadeOnDelete();
            $table->unsignedTinyInteger('day');
            $table->string('exercise');
            $table->unsignedTinyInteger('sets')->default(1);
            $table->unsignedTinyInteger('reps')->default(1);
            $table->decimal('rpe', 3, 1)->nullable();
            $table->unsignedInteger('load')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('program_day_exercises');
        Schema::dropIfExists('program_training_days');
        Schema::dropIfExists('program_weeks');
        Schema::dropIfExists('exercise_variants');
        Schema::dropIfExists('exercises');
    }

    private function migrateLegacyProgramData(): void
    {
        if (! Schema::hasTable('program_blocks')) {
            return;
        }

        $blocks = DB::table('program_blocks')->orderBy('id')->get();

        foreach ($blocks as $block) {
            $weekId = DB::table('program_weeks')->insertGetId([
                'template_id' => $block->template_id,
                'week_number' => $block->week,
                'block_type' => $this->mapBlockType($block->focus),
                'created_at' => $block->created_at,
                'updated_at' => $block->updated_at,
            ]);

            $sessions = DB::table('program_sessions')
                ->where('block_id', $block->id)
                ->orderBy('day')
                ->orderBy('id')
                ->get();

            $grouped = $sessions->groupBy('day');

            foreach ($grouped as $day => $daySessions) {
                $mainLift = $this->inferMainLift($daySessions->first()->exercise ?? '');

                $dayId = DB::table('program_training_days')->insertGetId([
                    'week_id' => $weekId,
                    'day_number' => (int) $day,
                    'main_lift' => $mainLift,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sortOrder = 0;
                foreach ($daySessions->values() as $index => $session) {
                    $section = match ($index) {
                        0 => 'topset',
                        1 => 'backoff',
                        default => 'accessory',
                    };

                    DB::table('program_day_exercises')->insert([
                        'training_day_id' => $dayId,
                        'exercise_variant_id' => null,
                        'section' => $section,
                        'exercise_name' => $session->exercise,
                        'sets' => $session->sets,
                        'reps' => $session->reps,
                        'load' => $session->load,
                        'rpe' => $session->rpe,
                        'sort_order' => $sortOrder++,
                        'created_at' => $session->created_at,
                        'updated_at' => $session->updated_at,
                    ]);
                }
            }
        }
    }

    private function mapBlockType(?string $focus): string
    {
        $normalized = strtolower(trim((string) $focus));

        return match (true) {
            str_contains($normalized, 'intens') => 'intensification',
            str_contains($normalized, 'peak') => 'peaking',
            default => 'volume',
        };
    }

    private function inferMainLift(string $exercise): string
    {
        $name = strtolower($exercise);

        if (str_contains($name, 'squat')) {
            return 'squat';
        }
        if (str_contains($name, 'bench')) {
            return 'bench';
        }
        if (str_contains($name, 'dead') || str_contains($name, 'soulev')) {
            return 'deadlift';
        }

        return 'squat';
    }
};
