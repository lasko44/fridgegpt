<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected $appends = ['is_subscribed'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'id',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_subscribed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    //region Attributes
    protected function isSubscribed(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->subscribed() && $this->subscriptions()->count() > 0,
        );
    }

    //endregion

    //region Relationships

    public function recipe(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    //endregion

    //region Functions

    public function subscribe(): void
    {
        $this->is_subscribed = true;
        $this->save();
    }

    public function recipeCount(): int
    {
        return $this->recipe()->count();
    }

    public function deleteOldestRecipe(): void
    {
        $this->recipe()->oldest()->first()?->delete();
    }

    public function dayRecipeCount(): int
    {
        // Assuming you want to count recipes created today
        return $this->recipe()
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }
    //endregion
}
