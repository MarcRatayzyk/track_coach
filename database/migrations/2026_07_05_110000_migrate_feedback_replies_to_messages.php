<?php

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\SessionFeedback;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('session_feedback_replies')) {
            return;
        }

        $replies = DB::table('session_feedback_replies')->orderBy('id')->get();

        foreach ($replies as $reply) {
            $feedback = DB::table('session_feedbacks')
                ->where('id', $reply->session_feedback_id)
                ->first();

            if ($feedback === null) {
                continue;
            }

            $threadId = DB::table('message_threads')
                ->where('coach_id', $feedback->coach_id)
                ->where('athlete_id', $feedback->athlete_id)
                ->value('id');

            if ($threadId === null) {
                $threadId = DB::table('message_threads')->insertGetId([
                    'coach_id' => $feedback->coach_id,
                    'athlete_id' => $feedback->athlete_id,
                    'created_at' => $reply->created_at ?? now(),
                    'updated_at' => $reply->updated_at ?? now(),
                ]);
            }

            $messageId = DB::table('messages')->insertGetId([
                'thread_id' => $threadId,
                'sender_id' => $reply->coach_id,
                'session_feedback_id' => $reply->session_feedback_id,
                'content' => $reply->body ?? '',
                'created_at' => $reply->created_at ?? now(),
                'updated_at' => $reply->updated_at ?? now(),
            ]);

            $audioFiles = DB::table('session_feedback_media')
                ->where('session_feedback_reply_id', $reply->id)
                ->orderBy('sort_order')
                ->get();

            foreach ($audioFiles as $media) {
                DB::table('message_media')->insert([
                    'message_id' => $messageId,
                    'kind' => $media->kind,
                    'disk' => $media->disk,
                    'path' => $media->path,
                    'mime_type' => $media->mime_type,
                    'original_name' => $media->original_name,
                    'size_bytes' => $media->size_bytes,
                    'sort_order' => $media->sort_order,
                    'created_at' => $media->created_at ?? now(),
                    'updated_at' => $media->updated_at ?? now(),
                ]);
            }
        }

        DB::table('session_feedback_media')
            ->whereNotNull('session_feedback_reply_id')
            ->delete();

        Schema::table('session_feedback_media', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('session_feedback_reply_id');
        });

        Schema::dropIfExists('session_feedback_replies');
    }

    public function down(): void
    {
        Schema::create('session_feedback_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_feedback_id')->constrained('session_feedbacks')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->timestamps();

            $table->unique('session_feedback_id');
        });

        Schema::table('session_feedback_media', function (Blueprint $table): void {
            $table->foreignId('session_feedback_reply_id')
                ->nullable()
                ->after('session_feedback_id')
                ->constrained('session_feedback_replies')
                ->cascadeOnDelete();
        });
    }
};
