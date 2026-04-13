<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A support ticket conversation between a user and the admin team.
 */
class SupportConversation extends Model
{
    public const STATUS_AWAITING_ADMIN = 'awaiting_admin';
    public const STATUS_AWAITING_USER = 'awaiting_user';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'last_message_by_admin' => 'boolean',
            'user_last_read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $conv) {
            if (empty($conv->uuid)) {
                $conv->uuid = (string) Str::uuid();
            }
            if (empty($conv->reference)) {
                $conv->reference = self::generateReference();
            }
            if (empty($conv->last_message_at)) {
                $conv->last_message_at = now();
            }
        });
    }

    /**
     * Use the short reference (FG-A4B7C2) for routing — easier to read in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * Generate a short, unique, human-friendly reference like FG-A4B7C2.
     * Avoids confusing characters (0/O, 1/I/L).
     */
    public static function generateReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = "FG-{$code}";
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_AWAITING_ADMIN, self::STATUS_AWAITING_USER], true);
    }
}
