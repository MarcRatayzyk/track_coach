<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_chart_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->json('config');
            $table->timestamps();
        });

        Schema::create('coach_stats_dashboard_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->string('item_type');
            $table->string('builtin_key')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('coach_chart_templates')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $this->seedDefaultDashboardItemsForExistingCoaches();
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_stats_dashboard_items');
        Schema::dropIfExists('coach_chart_templates');
    }

    private function seedDefaultDashboardItemsForExistingCoaches(): void
    {
        $coachIds = DB::table('users')
            ->where('role', 'coach')
            ->pluck('id');

        $builtinKeys = [
            'volume_weekly',
            'topset_e1rm',
            'volume_distribution',
            'avg_load_weekly',
        ];

        $now = now();

        foreach ($coachIds as $coachId) {
            foreach ($builtinKeys as $index => $builtinKey) {
                DB::table('coach_stats_dashboard_items')->insert([
                    'coach_id' => $coachId,
                    'item_type' => 'builtin',
                    'builtin_key' => $builtinKey,
                    'template_id' => null,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
