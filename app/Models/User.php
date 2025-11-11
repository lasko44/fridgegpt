<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * @mixin Builder
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    protected $appends = ['is_subscribed', 'bill_period_end'];
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

    /**
     * @return void
     * Boot the model.
     */
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

    protected function billPeriodEnd(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currentBillPeriodEnd(),
        );
    }

    //endregion

    //region Scopes

    /**
     * Scope a query to only include users who have remove_subscribed_on as today.
     */
    #[Scope]
    protected function toRemoveSubscribedToday( Builder $query): Builder
    {
        return $query->whereDate('remove_subscribed_on', now()->toDateString());
    }

    /**
     * Scope a query to only include users who are to be removed this month.
     */
    #[Scope]
    protected function toBeRemovedSubscribedThisMonth(Builder $query): Builder
    {
        return $query->whereDate('remove_subscribed_on', now()->toDateString());
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

    public function unsubscribe(): void
    {
        $this->is_subscribed = false;
        $this->save();
    }

    public function currentBillPeriodEnd(): ?Carbon
    {
        if($this->subscription()){

            return Carbon::parse($this->subscription()->asStripeSubscription()->current_period_end);
        }
        return null;
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
