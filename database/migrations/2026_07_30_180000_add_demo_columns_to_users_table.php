<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false)->after('role');
            $table->timestamp('demo_expires_at')->nullable()->after('is_demo');
        });

        // Grandfather existing coaches so they are not hard-blocked after deploy.
        if (Schema::hasColumn('users', 'trial_ends_at')) {
            DB::table('users')
                ->where('role', 'coach')
                ->where('is_demo', false)
                ->whereNull('trial_ends_at')
                ->update(['trial_ends_at' => now()->addDays(14)]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_demo', 'demo_expires_at']);
        });
    }
};
