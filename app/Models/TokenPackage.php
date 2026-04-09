<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class TokenPackage extends Model
{
    protected $fillable = [
        'name', 'tokens', 'price_cents', 'stripe_price_id',
        'savings_percent', 'is_popular', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tokens' => 'integer',
            'price_cents' => 'integer',
            'savings_percent' => 'integer',
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    // Accessors

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->price_cents / 100,
        );
    }

    protected function pricePerToken(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->tokens > 0 ? round($this->price_cents / $this->tokens / 100, 3) : 0,
        );
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: fn() => '$' . number_format($this->price, 2),
        );
    }
}
