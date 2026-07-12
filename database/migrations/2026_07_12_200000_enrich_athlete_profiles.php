<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('athlete_profiles', function (Blueprint $table): void {
            $table->unsignedSmallInteger('height_cm')->nullable()->after('birth_date');
            $table->string('sex', 8)->nullable()->after('height_cm');
            $table->string('weight_category', 16)->nullable()->after('sex');
            $table->string('level', 16)->nullable()->after('weight_category');
            $table->text('injuries_notes')->nullable()->after('level');
        });

        $this->migrateWeightClassToCategory();

        Schema::table('athlete_profiles', function (Blueprint $table): void {
            $table->dropColumn('weight_class');
        });
    }

    public function down(): void
    {
        Schema::table('athlete_profiles', function (Blueprint $table): void {
            $table->string('weight_class')->nullable()->after('birth_date');
        });

        DB::table('athlete_profiles')->orderBy('id')->each(function ($row): void {
            $label = $this->categoryToLegacyLabel($row->weight_category);
            if ($label !== null) {
                DB::table('athlete_profiles')->where('id', $row->id)->update(['weight_class' => $label]);
            }
        });

        Schema::table('athlete_profiles', function (Blueprint $table): void {
            $table->dropColumn(['height_cm', 'sex', 'weight_category', 'level', 'injuries_notes']);
        });
    }

    private function migrateWeightClassToCategory(): void
    {
        DB::table('athlete_profiles')->orderBy('id')->each(function ($row): void {
            if ($row->weight_class === null || $row->weight_class === '') {
                return;
            }

            $category = $this->legacyLabelToCategory((string) $row->weight_class);
            if ($category !== null) {
                DB::table('athlete_profiles')->where('id', $row->id)->update([
                    'weight_category' => $category,
                ]);
            }
        });
    }

    private function legacyLabelToCategory(string $label): ?string
    {
        $normalized = strtolower(trim(str_replace(['kg', ' ', '-'], '', $label)));

        $maleMap = [
            '53' => 'm53', '59' => 'm59', '66' => 'm66', '74' => 'm74',
            '83' => 'm83', '93' => 'm93', '105' => 'm105', '120' => 'm120',
        ];
        $femaleMap = [
            '43' => 'f43', '47' => 'f47', '52' => 'f52', '57' => 'f57',
            '63' => 'f63', '69' => 'f69', '76' => 'f76', '84' => 'f84',
        ];

        if (isset($maleMap[$normalized])) {
            return $maleMap[$normalized];
        }
        if (isset($femaleMap[$normalized])) {
            return $femaleMap[$normalized];
        }

        if (str_contains($label, '+') || str_contains($label, '120')) {
            return 'm120plus';
        }
        if (str_contains($label, '84+') || str_contains($label, '84 +')) {
            return 'f84plus';
        }

        return null;
    }

    private function categoryToLegacyLabel(?string $category): ?string
    {
        $labels = [
            'm53' => '53 kg', 'm59' => '59 kg', 'm66' => '66 kg', 'm74' => '74 kg',
            'm83' => '83 kg', 'm93' => '93 kg', 'm105' => '105 kg', 'm120' => '120 kg',
            'm120plus' => '120+ kg', 'f43' => '43 kg', 'f47' => '47 kg', 'f52' => '52 kg',
            'f57' => '57 kg', 'f63' => '63 kg', 'f69' => '69 kg', 'f76' => '76 kg',
            'f84' => '84 kg', 'f84plus' => '84+ kg',
        ];

        return $category !== null ? ($labels[$category] ?? null) : null;
    }
};
