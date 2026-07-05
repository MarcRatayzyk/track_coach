<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_feedback_annotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_feedback_media_id')->constrained('session_feedback_media')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('timestamp_ms')->default(0);
            $table->text('body')->nullable();
            $table->json('shapes')->nullable();
            $table->timestamps();

            $table->index(['session_feedback_media_id', 'timestamp_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_feedback_annotations');
    }
};
