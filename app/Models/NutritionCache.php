<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionCache extends Model
{
    protected $table = 'nutrition_cache';

    protected $fillable = ['lookup_key', 'source', 'product_name', 'nutrition'];

    protected function casts(): array
    {
        return [
            'nutrition' => 'array',
        ];
    }

    public static function findByKey(string $key): ?self
    {
        return static::where('lookup_key', $key)->first();
    }
}
