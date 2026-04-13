<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Admin-authored message sent to one user (direct) or all users (broadcast).
 */
class AdminMessage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sent_push' => 'boolean',
            'recipients_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $message) {
            if (empty($message->uuid)) {
                $message->uuid = (string) Str::uuid();
            }
        });
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function readBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_message_reads')
            ->withPivot('read_at');
    }

    public function isBroadcast(): bool
    {
        return $this->recipient_id === null;
    }
}
