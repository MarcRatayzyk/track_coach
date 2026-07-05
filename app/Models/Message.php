<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'thread_id',
        'sender_id',
        'session_feedback_id',
        'content',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function sessionFeedback(): BelongsTo
    {
        return $this->belongsTo(SessionFeedback::class, 'session_feedback_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(MessageMedia::class)->orderBy('sort_order');
    }

    public function audioFiles(): HasMany
    {
        return $this->hasMany(MessageMedia::class)
            ->where('kind', MessageMedia::KIND_AUDIO)
            ->orderBy('sort_order');
    }
}
