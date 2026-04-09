<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TokenTransaction extends Model
{
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SPEND = 'spend';
    public const TYPE_REFUND = 'refund';
    public const TYPE_BONUS = 'bonus';

    public $timestamps = false;

    protected $fillable = [
        'uuid', 'user_id', 'type', 'amount', 'balance_after', 'description', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    public function scopePurchases(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PURCHASE);
    }

    public function scopeSpending(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SPEND);
    }

    // Helpers

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    protected function absoluteAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => abs($this->amount),
        );
    }

    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->amount > 0 ? '+' : '') . $this->amount,
        );
    }
}
