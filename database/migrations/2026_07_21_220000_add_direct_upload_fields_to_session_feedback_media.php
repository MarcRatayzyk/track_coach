<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('session_feedback_media', 'uploaded_by')) {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->foreignId('uploaded_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('session_feedback_media', 'status')) {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->string('status')->default('attached');
            });
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            $indexExists = collect(DB::select("
                SELECT 1 AS ok
                FROM pg_indexes
                WHERE schemaname = current_schema()
                  AND tablename = 'session_feedback_media'
                  AND indexname = 'session_feedback_media_uploaded_by_status_index'
            "))->isNotEmpty();

            if (! $indexExists) {
                Schema::table('session_feedback_media', function (Blueprint $table): void {
                    $table->index(['uploaded_by', 'status']);
                });
            }
        } else {
            try {
                Schema::table('session_feedback_media', function (Blueprint $table): void {
                    $table->index(['uploaded_by', 'status']);
                });
            } catch (\Throwable) {
                // Index may already exist.
            }
        }

        $this->backfillOwnership();
        $this->makeSessionFeedbackIdNullable();
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSessionFeedbackMediaSqlite(nullableFeedbackId: false);

            return;
        }

        $this->dropForeignKeyIfExists('session_feedback_media_session_feedback_id_foreign');

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE session_feedback_media ALTER COLUMN session_feedback_id SET NOT NULL');
        } else {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->unsignedBigInteger('session_feedback_id')->nullable(false)->change();
            });
        }

        Schema::table('session_feedback_media', function (Blueprint $table): void {
            $table->foreign('session_feedback_id')
                ->references('id')
                ->on('session_feedbacks')
                ->cascadeOnDelete();
        });

        if (Schema::hasColumn('session_feedback_media', 'uploaded_by')) {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('uploaded_by');
            });
        }

        if (Schema::hasColumn('session_feedback_media', 'status')) {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->dropColumn('status');
            });
        }
    }

    private function backfillOwnership(): void
    {
        $rows = DB::table('session_feedback_media as m')
            ->join('session_feedbacks as f', 'f.id', '=', 'm.session_feedback_id')
            ->whereNull('m.uploaded_by')
            ->select('m.id', 'f.athlete_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('session_feedback_media')
                ->where('id', $row->id)
                ->update([
                    'uploaded_by' => $row->athlete_id,
                    'status' => 'attached',
                ]);
        }
    }

    private function makeSessionFeedbackIdNullable(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $nullable = collect(DB::select('PRAGMA table_info(session_feedback_media)'))
                ->firstWhere('name', 'session_feedback_id');

            if ($nullable && (int) $nullable->notnull === 1) {
                $this->rebuildSessionFeedbackMediaSqlite(nullableFeedbackId: true);
            }

            return;
        }

        if ($driver === 'pgsql') {
            $isNullable = DB::selectOne("
                SELECT is_nullable
                FROM information_schema.columns
                WHERE table_schema = current_schema()
                  AND table_name = 'session_feedback_media'
                  AND column_name = 'session_feedback_id'
            ");

            if ($isNullable && $isNullable->is_nullable === 'YES') {
                return;
            }

            $this->dropForeignKeyIfExists('session_feedback_media_session_feedback_id_foreign');
            DB::statement('ALTER TABLE session_feedback_media ALTER COLUMN session_feedback_id DROP NOT NULL');
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->foreign('session_feedback_id')
                    ->references('id')
                    ->on('session_feedbacks')
                    ->cascadeOnDelete();
            });

            return;
        }

        Schema::table('session_feedback_media', function (Blueprint $table): void {
            $table->dropForeign(['session_feedback_id']);
        });

        Schema::table('session_feedback_media', function (Blueprint $table): void {
            $table->unsignedBigInteger('session_feedback_id')->nullable()->change();
            $table->foreign('session_feedback_id')
                ->references('id')
                ->on('session_feedbacks')
                ->cascadeOnDelete();
        });
    }

    private function dropForeignKeyIfExists(string $foreignName): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE session_feedback_media DROP CONSTRAINT IF EXISTS {$foreignName}");

            return;
        }

        try {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->dropForeign(['session_feedback_id']);
            });
        } catch (\Throwable) {
            // Already dropped.
        }
    }

    private function rebuildSessionFeedbackMediaSqlite(bool $nullableFeedbackId): void
    {
        Schema::disableForeignKeyConstraints();

        $rows = DB::table('session_feedback_media')->get();

        Schema::drop('session_feedback_media');

        Schema::create('session_feedback_media', function (Blueprint $table) use ($nullableFeedbackId): void {
            $table->id();
            if ($nullableFeedbackId) {
                $table->foreignId('session_feedback_id')->nullable()->constrained('session_feedbacks')->cascadeOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            } else {
                $table->foreignId('session_feedback_id')->constrained('session_feedbacks')->cascadeOnDelete();
            }
            $table->string('kind');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            if ($nullableFeedbackId) {
                $table->string('status')->default('attached');
            }
            $table->timestamps();

            $table->index(['session_feedback_id', 'kind']);
            if ($nullableFeedbackId) {
                $table->index(['uploaded_by', 'status']);
            }
        });

        foreach ($rows as $row) {
            if (! $nullableFeedbackId && $row->session_feedback_id === null) {
                continue;
            }

            $payload = [
                'id' => $row->id,
                'session_feedback_id' => $row->session_feedback_id,
                'kind' => $row->kind,
                'disk' => $row->disk,
                'path' => $row->path,
                'mime_type' => $row->mime_type,
                'original_name' => $row->original_name,
                'size_bytes' => $row->size_bytes,
                'sort_order' => $row->sort_order,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];

            if ($nullableFeedbackId) {
                $payload['uploaded_by'] = $row->uploaded_by ?? null;
                $payload['status'] = $row->status ?? 'attached';
            }

            DB::table('session_feedback_media')->insert($payload);
        }

        Schema::enableForeignKeyConstraints();
    }
};
