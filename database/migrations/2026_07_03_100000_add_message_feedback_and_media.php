<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->foreignId('session_feedback_id')
                ->nullable()
                ->after('sender_id')
                ->constrained('session_feedbacks')
                ->nullOnDelete();
        });

        Schema::create('message_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->string('kind');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['message_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_media');

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('session_feedback_id');
        });
    }
};
