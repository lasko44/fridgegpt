<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TokenCode extends Model
{
    public const TYPE_PROMO = 'promo';
    public const TYPE_SINGLE = 'single';

    protected $fillable = [
        'code', 'type', 'tokens', 'max_uses', 'uses_count',
        'expires_at', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tokens' => 'integer',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $code) {
            if (empty($code->code)) {
                $code->code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    // Relationships

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(TokenCodeRedemption::class);
    }

    // Validation

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) return false;
        return true;
    }

    public function hasBeenRedeemedBy(User $user): bool
    {
        return $this->redemptions()->where('user_id', $user->id)->exists();
    }

    public function canBeRedeemedBy(User $user): array
    {
        if (!$this->isValid()) {
            return ['valid' => false, 'reason' => 'This code is no longer valid.'];
        }
        if ($this->hasBeenRedeemedBy($user)) {
            return ['valid' => false, 'reason' => 'You have already redeemed this code.'];
        }
        return ['valid' => true, 'reason' => null];
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function scopeHasRemainingUses(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('max_uses')->orWhereColumn('uses_count', '<', 'max_uses');
        });
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->active()->notExpired()->hasRemainingUses();
    }
}
