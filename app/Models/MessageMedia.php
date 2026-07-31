<?php

namespace App\Models;

use App\Support\PrivateMediaDisk;
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
        if (PrivateMediaDisk::isObjectStore($this->disk)) {
            return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addHour());
        }

        return route('media.messages.show', $this);
    }
}
