<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageThread extends Model
{
    protected $fillable = [
        'coach_id',
        'athlete_id',
    ];

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    public function scopeWithUnreadCountFor(Builder $query, User $user): Builder
    {
        return $query->withCount([
            'messages as unread_messages_count' => fn (Builder $messages) => $messages
                ->whereNull('read_at')
                ->where('sender_id', '!=', $user->id),
        ]);
    }

    public function scopeOrderedForInbox(Builder $query, User $user): Builder
    {
        return $query
            ->withUnreadCountFor($user)
            ->orderByDesc('unread_messages_count')
            ->orderByDesc('updated_at');
    }

    public function markAsReadFor(User $user): void
    {
        $this->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now()]);
    }
}
