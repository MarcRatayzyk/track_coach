<?php

use App\Models\Exercise;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table): void {
            $table->foreignId('parent_exercise_id')
                ->nullable()
                ->after('movement_pattern')
                ->constrained('exercises')
                ->nullOnDelete();
        });

        $groups = DB::table('exercises')
            ->where('is_custom', false)
            ->where('category', Exercise::CATEGORY_ACCESSORY)
            ->pluck('id', 'slug');

        $customs = DB::table('exercises')
            ->where('is_custom', true)
            ->where('category', Exercise::CATEGORY_ACCESSORY)
            ->get(['id', 'lift', 'movement_pattern']);

        foreach ($customs as $exercise) {
            $slug = Exercise::inferAccessoryGroupSlug($exercise->lift, $exercise->movement_pattern);
            $parentId = $groups[$slug] ?? null;

            if ($parentId) {
                DB::table('exercises')
                    ->where('id', $exercise->id)
                    ->update(['parent_exercise_id' => $parentId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_exercise_id');
        });
    }
};
