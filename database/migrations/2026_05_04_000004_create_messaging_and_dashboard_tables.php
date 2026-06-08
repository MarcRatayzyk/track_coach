<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_threads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['coach_id', 'athlete_id']);
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id')->constrained('message_threads')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['thread_id', 'created_at']);
        });

        Schema::create('dashboard_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('athlete_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->timestamp('due_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index(['coach_id', 'status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_tasks');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_threads');
    }
};
