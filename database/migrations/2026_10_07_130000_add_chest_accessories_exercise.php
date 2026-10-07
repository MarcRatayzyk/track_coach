<?php

use App\Models\Exercise;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $exerciseId = DB::table('exercises')
            ->where('slug', 'pectoraux-accessoire')
            ->where('is_custom', false)
            ->value('id');

        if (! $exerciseId) {
            $exerciseId = DB::table('exercises')->insertGetId([
                'coach_id' => null,
                'is_custom' => false,
                'name' => 'Pectoraux accessoire',
                'slug' => 'pectoraux-accessoire',
                'lift' => Exercise::LIFT_BENCH,
                'category' => Exercise::CATEGORY_ACCESSORY,
                'equipment' => 'machine',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $variants = ['Pec fly', 'Développé machine pectoraux', 'Écarté poulie'];

        foreach ($variants as $variantName) {
            $slug = Str::slug($variantName);

            $exists = DB::table('exercise_variants')
                ->where('exercise_id', $exerciseId)
                ->where('slug', $slug)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('exercise_variants')->insert([
                'exercise_id' => $exerciseId,
                'name' => $variantName,
                'slug' => $slug,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $customs = DB::table('exercises')
            ->where('is_custom', true)
            ->where('category', Exercise::CATEGORY_ACCESSORY)
            ->get(['id', 'lift', 'movement_pattern']);

        foreach ($customs as $exercise) {
            $slug = Exercise::inferAccessoryGroupSlug($exercise->lift, $exercise->movement_pattern);

            if ($slug !== 'pectoraux-accessoire') {
                continue;
            }

            DB::table('exercises')
                ->where('id', $exercise->id)
                ->update(['parent_exercise_id' => $exerciseId]);
        }
    }

    public function down(): void
    {
        $exerciseId = DB::table('exercises')
            ->where('slug', 'pectoraux-accessoire')
            ->where('is_custom', false)
            ->value('id');

        if (! $exerciseId) {
            return;
        }

        DB::table('exercises')
            ->where('parent_exercise_id', $exerciseId)
            ->update(['parent_exercise_id' => null]);

        DB::table('exercise_variants')->where('exercise_id', $exerciseId)->delete();
        DB::table('exercises')->where('id', $exerciseId)->delete();
    }
};
