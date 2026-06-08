<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExerciseLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            [
                'name' => 'Squat',
                'lift' => Exercise::LIFT_SQUAT,
                'category' => Exercise::CATEGORY_MAIN_LIFT,
                'equipment' => 'barbell',
                'variants' => ['Squat', 'Squat pause', 'Squat tempo', 'Squat pin', 'Box squat'],
            ],
            [
                'name' => 'Bench press',
                'lift' => Exercise::LIFT_BENCH,
                'category' => Exercise::CATEGORY_MAIN_LIFT,
                'equipment' => 'barbell',
                'variants' => ['Comp Bench','Bench', 'Bench pause', 'Spoto press', 'Larsen press', 'Close grip bench'],
            ],
            [
                'name' => 'Deadlift',
                'lift' => Exercise::LIFT_DEADLIFT,
                'category' => Exercise::CATEGORY_MAIN_LIFT,
                'equipment' => 'barbell',
                'variants' => ['Deadlift conventionnel', 'Deadlift sumo', 'Romanian deadlift'],
            ],
            [
                'name' => 'Rowing haltère',
                'lift' => Exercise::LIFT_GENERAL,
                'category' => Exercise::CATEGORY_ACCESSORY,
                'equipment' => 'dumbbell',
                'variants' => ['Rowing haltère', 'Rowing machine'],
            ],
            [
                'name' => 'Triceps',
                'lift' => Exercise::LIFT_BENCH,
                'category' => Exercise::CATEGORY_ACCESSORY,
                'equipment' => 'barbell',
                'variants' => ['Skull crusher', 'Extension triceps poulie', 'Dips'],
            ],
            [
                'name' => 'Épaules',
                'lift' => Exercise::LIFT_BENCH,
                'category' => Exercise::CATEGORY_ACCESSORY,
                'equipment' => 'dumbbell',
                'variants' => ['Développé militaire', 'Élévations latérales', 'Face pull'],
            ],
            [
                'name' => 'Jambes accessoire',
                'lift' => Exercise::LIFT_SQUAT,
                'category' => Exercise::CATEGORY_ACCESSORY,
                'equipment' => 'machine',
                'variants' => ['Leg press', 'Leg curl', 'Leg extension', 'Fentes marchées'],
            ],
            [
                'name' => 'Dos accessoire',
                'lift' => Exercise::LIFT_DEADLIFT,
                'category' => Exercise::CATEGORY_ACCESSORY,
                'equipment' => 'cable',
                'variants' => ['Tirage poulie', 'Pull-over', 'Hyperextension'],
            ],
        ];

        foreach ($catalog as $item) {
            $slug = Str::slug($item['name']);

            $exercise = Exercise::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $item['name'],
                    'lift' => $item['lift'],
                    'category' => $item['category'],
                    'equipment' => $item['equipment'],
                ],
            );

            foreach ($item['variants'] as $variantName) {
                $variantSlug = Str::slug($variantName);

                $exercise->variants()->updateOrCreate(
                    ['slug' => $variantSlug],
                    ['name' => $variantName],
                );
            }
        }
    }
}
