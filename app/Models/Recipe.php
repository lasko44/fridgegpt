<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Recipe extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = [
        'user_id',
        'id',
        'embedding',
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

    public function mealPlanSlots(): HasMany
    {
        return $this->hasMany(MealPlanSlot::class);
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

    /**
     * Store a vector embedding using parameterized pgvector query.
     */
    public function setEmbedding(array $embedding): void
    {
        $vector = '[' . implode(',', $embedding) . ']';
        DB::statement(
            'UPDATE recipes SET embedding = ?::vector WHERE id = ?',
            [$vector, $this->id]
        );
    }

    /**
     * Get the embedding as a PHP array.
     */
    public function getEmbedding(): ?array
    {
        $result = DB::selectOne(
            'SELECT embedding::text FROM recipes WHERE id = ?',
            [$this->id]
        );

        if (!$result || !$result->embedding) {
            return null;
        }

        return array_map('floatval', explode(',', trim($result->embedding, '[]')));
    }

    /**
     * Find similar recipes using cosine similarity via pgvector.
     *
     * @param array $embedding The query embedding vector
     * @param int $limit Max results to return
     * @param float $threshold Minimum similarity (0-1, higher = more similar)
     */
    public static function findSimilar(array $embedding, int $limit = 5, float $threshold = 0.3): array
    {
        $vector = '[' . implode(',', $embedding) . ']';

        return DB::select(
            "SELECT *, 1 - (embedding <=> ?::vector) as similarity
             FROM recipes
             WHERE embedding IS NOT NULL
             AND deleted_at IS NULL
             AND 1 - (embedding <=> ?::vector) > ?
             ORDER BY embedding <=> ?::vector
             LIMIT ?",
            [$vector, $vector, $threshold, $vector, $limit]
        );
    }

    /**
     * Recalculate nutrition totals from ingredients.
     */
    public function recalculateNutrition(): void
    {
        $ingredients = $this->ingredients()->whereNotNull('calories')->get();

        if ($ingredients->isEmpty()) {
            return;
        }

        $servings = $this->servings ?: 1;

        $this->update([
            'calories_per_serving' => round($ingredients->sum('calories') / $servings, 2),
            'protein_per_serving' => round($ingredients->sum('protein') / $servings, 2),
            'carbs_per_serving' => round($ingredients->sum('carbs') / $servings, 2),
            'fat_per_serving' => round($ingredients->sum('fat') / $servings, 2),
            'nutrition_source' => 'refined',
        ]);
    }
}
