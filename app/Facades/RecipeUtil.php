<?php

namespace App\Facades;

use App\Models\User;
use App\Services\RecipeService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static RecipeService generateRecipe(array $ingredients)
 * @method static string get()
 * @method static array toArray()
 * @method static string toJson()
 * @method static string title()
 * @method static array userIngredients()
 * @method static array parse()
 * @method static array ingredients()
 * @method static RecipeService guestStore(array $ingredients, string $ip)
 * @method static RecipeService standardStore(array $ingredients, User $user)
 * @method static RecipeService premiumStore(array $ingredients, User $user)
 * @method static array getGuestRecipes()
 * @method static array getStandardRecipes()
 * @method static LengthAwarePaginator getPremiumRecipes()
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