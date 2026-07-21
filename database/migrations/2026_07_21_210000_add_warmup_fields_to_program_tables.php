<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_templates', function (Blueprint $table) {
            $table->text('default_warmup_notes')->nullable()->after('table_layout');
            $table->json('default_warmup_items')->nullable()->after('default_warmup_notes');
        });

        Schema::table('program_training_days', function (Blueprint $table) {
            $table->boolean('warmup_override')->default(false)->after('notes');
            $table->text('warmup_notes')->nullable()->after('warmup_override');
        });
    }

    public function down(): void
    {
        Schema::table('program_templates', function (Blueprint $table) {
            $table->dropColumn(['default_warmup_notes', 'default_warmup_items']);
        });

        Schema::table('program_training_days', function (Blueprint $table) {
            $table->dropColumn(['warmup_override', 'warmup_notes']);
        });
    }
};
