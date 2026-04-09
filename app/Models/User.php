<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @mixin Builder
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
        'id',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'token_balance' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
            // Give new users free tokens on signup
            if (!isset($user->token_balance)) {
                $user->token_balance = (int) config('tokens.free_tokens_on_signup', 5);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    // Relationships

    public function recipe(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function tokenTransactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    // Helpers

    public function recipeCount(): int
    {
        return $this->recipe()->count();
    }
}
