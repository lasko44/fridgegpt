<?php

namespace App\Facades;

use App\Models\Recipe;
use App\Models\User;
use App\Services\VariationService;
use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\VariationService
 * @method static VariationService generate(array $data)
 * @method static array getIngredients()
 * @method static void setIngredients(array $ingredients)
 * @method static array getRestrictions()
 * @method static void setRestrictions(array $restrictions)
 * @method static string getPortion()
 * @method static void setPortion(string $portion)
 * @method static int getServings()
 * @method static void setServings(int $servings)
 * @method static string getDescription()
 * @method static void setDescription(string $description)
 * @method static void setAllProperties(array $data)
 * @method static int getRecipeId()
 * @method static void setRecipeId(int $recipeId)
 * @method static mixed getRecipeVariation()
 * @method static Recipe store(User $user)
 */
class Variation extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return VariationService::class;
    }
}
