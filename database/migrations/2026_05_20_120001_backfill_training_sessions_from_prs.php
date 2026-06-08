<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $records = DB::table('personal_records')
            ->orderBy('reference_date')
            ->orderBy('id')
            ->get();

        foreach ($records as $record) {
            $exists = DB::table('training_sessions')
                ->where('athlete_id', $record->athlete_id)
                ->whereDate('session_date', $record->reference_date)
                ->where('squat', $record->squat)
                ->where('bench', $record->bench)
                ->where('deadlift', $record->deadlift)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('training_sessions')->insert([
                'athlete_id' => $record->athlete_id,
                'session_date' => $record->reference_date,
                'squat' => $record->squat,
                'bench' => $record->bench,
                'deadlift' => $record->deadlift,
                'notes' => null,
                'created_at' => $record->created_at,
                'updated_at' => $record->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        // No rollback: sessions may have been added manually after backfill.
    }
};
