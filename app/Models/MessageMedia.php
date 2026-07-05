<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MessageMedia extends Model
{
    public const KIND_AUDIO = 'audio';

    protected $table = 'message_media';

    protected $fillable = [
        'message_id',
        'kind',
        'disk',
        'path',
        'mime_type',
        'original_name',
        'size_bytes',
        'sort_order',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
