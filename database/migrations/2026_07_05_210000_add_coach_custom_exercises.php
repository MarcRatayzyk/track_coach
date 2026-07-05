<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->foreignId('coach_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->boolean('is_custom')->default(false)->after('coach_id');
            $table->unique(['coach_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table): void {
            $table->dropUnique(['coach_id', 'slug']);
            $table->dropConstrainedForeignId('coach_id');
            $table->dropColumn('is_custom');
            $table->unique('slug');
        });
    }
};
