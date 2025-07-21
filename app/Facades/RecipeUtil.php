<?php

namespace App\Facades;

use App\Services\RecipeService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static RecipeService generateRecipe(array $ingredients)
 * @method static string get()
 * @method static array toArray()
 * @method static string toJson()
 * @method static string title()
 * @method static array userIngredients()
 * @method static array parse()
 */
class RecipeUtil extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return RecipeService::class;
    }

}