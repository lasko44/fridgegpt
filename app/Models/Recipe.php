<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Recipe extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = [
        'user_id',
        'id',
    ];

    //use the slug as the route key
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    public function recipeRestriction (): HasMany
    {
        return $this->hasMany(RecipeRestriction::class);

    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::parse($value)
                ->timezone(Cache::get('user_timezone', 'UTC'))
                ->format(
                    Carbon::parse($value)->gt(Carbon::now()->subMinutes(5)) ? '\J\u\s\t\ \n\o\w' :
                        Carbon::parse($value)->format('Y-m-d')
                )
        );
    }
}
