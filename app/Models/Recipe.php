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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::parse($value)
                ->timezone(Cache::get('user_timezone', 'UTC'))
                ->format(
                    Carbon::parse($value)->isToday() ? '\T\o\d\a\y \a\t g:i A' :
                        (Carbon::parse($value)->isYesterday() ? '\Y\e\s\t\e\r\d\a\y \a\t g:i A' : 'M d \a\t g:i A')
                )
        );
    }
}
