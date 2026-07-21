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
                    ->after('session_feedback_id')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->string('status')->default('attached')->after('sort_order');
                $table->index(['uploaded_by', 'status']);
            });
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
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
        } else {
            DB::table('session_feedback_media')
                ->join('session_feedbacks', 'session_feedbacks.id', '=', 'session_feedback_media.session_feedback_id')
                ->whereNull('session_feedback_media.uploaded_by')
                ->update([
                    'session_feedback_media.uploaded_by' => DB::raw('session_feedbacks.athlete_id'),
                    'session_feedback_media.status' => 'attached',
                ]);
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $nullable = collect(DB::select('PRAGMA table_info(session_feedback_media)'))
                ->firstWhere('name', 'session_feedback_id');

            if ($nullable && (int) $nullable->notnull === 1) {
                $this->rebuildSessionFeedbackMediaSqlite(nullableFeedbackId: true);
            }

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

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->rebuildSessionFeedbackMediaSqlite(nullableFeedbackId: false);
        } else {
            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->dropForeign(['session_feedback_id']);
            });

            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->unsignedBigInteger('session_feedback_id')->nullable(false)->change();
                $table->foreign('session_feedback_id')
                    ->references('id')
                    ->on('session_feedbacks')
                    ->cascadeOnDelete();
            });

            Schema::table('session_feedback_media', function (Blueprint $table): void {
                $table->dropForeign(['uploaded_by']);
                $table->dropIndex(['uploaded_by', 'status']);
                $table->dropColumn(['uploaded_by', 'status']);
            });
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
