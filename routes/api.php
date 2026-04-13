<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\SocialAuthController;
use App\Http\Controllers\Api\V1\Barcode\BarcodeLookupController;
use App\Http\Controllers\Api\V1\Recipe\RecipeController;
use App\Http\Controllers\Api\V1\MealPlan\MealPlanController as ApiMealPlanController;
use App\Http\Controllers\Api\V1\MealPlan\PendingMealPlansController;
use App\Http\Controllers\Api\V1\Token\TokenController;
use App\Http\Controllers\Api\V1\User\RegisterPushTokenController;
use App\Http\Controllers\Api\V1\User\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public Routes
    Route::post('auth/login', [LoginController::class, 'store'])->name('api.v1.auth.login');
    Route::post('auth/register', [RegisterController::class, 'store'])->name('api.v1.auth.register');

    Route::post('auth/google/callback', [SocialAuthController::class, 'googleCallback'])
        ->name('api.v1.auth.google.callback');
    Route::post('auth/facebook/callback', [SocialAuthController::class, 'facebookCallback'])
        ->name('api.v1.auth.facebook.callback');

    Route::post('recipes/guest', [RecipeController::class, 'guestStore'])
        ->middleware('throttle:3,1440')
        ->name('api.v1.recipes.guest');

    Route::post('ingredients/suggest', [RecipeController::class, 'suggestIngredients'])
        ->middleware('throttle:30,1')
        ->name('api.v1.ingredients.suggest');

    // Barcode lookup (FREE, no auth required)
    Route::post('barcode/lookup', BarcodeLookupController::class)
        ->middleware('throttle:30,1')
        ->name('api.v1.barcode.lookup');

    // Authenticated Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [LogoutController::class, 'destroy'])->name('api.v1.auth.logout');

        // User
        Route::get('user', [UserController::class, 'show'])->name('api.v1.user.show');
        Route::put('user', [UserController::class, 'update'])->name('api.v1.user.update');
        Route::post('user/push-token', RegisterPushTokenController::class)->name('api.v1.user.push-token');

        // Recipes
        Route::get('recipes', [RecipeController::class, 'index'])->name('api.v1.recipes.index');
        Route::post('recipes', [RecipeController::class, 'store'])->name('api.v1.recipes.store');
        Route::get('recipes/{recipe}', [RecipeController::class, 'show'])->name('api.v1.recipes.show');
        Route::delete('recipes/{recipe}', [RecipeController::class, 'destroy'])->name('api.v1.recipes.destroy');

        // Tokens
        Route::get('tokens', [TokenController::class, 'index'])->name('api.v1.tokens.index');
        Route::get('tokens/balance', [TokenController::class, 'balance'])->name('api.v1.tokens.balance');
        Route::post('tokens/checkout', [TokenController::class, 'checkout'])->name('api.v1.tokens.checkout');
        Route::post('tokens/redeem', [TokenController::class, 'redeem'])->name('api.v1.tokens.redeem');

        // Meal Plans
        Route::get('meal-plans', [ApiMealPlanController::class, 'index'])->name('api.v1.meal-plans.index');
        Route::get('meal-plans/pending', PendingMealPlansController::class)->name('api.v1.meal-plans.pending');
        Route::post('meal-plans', [ApiMealPlanController::class, 'store'])->name('api.v1.meal-plans.store');
        Route::get('meal-plans/{mealPlan}', [ApiMealPlanController::class, 'show'])->name('api.v1.meal-plans.show');
        Route::delete('meal-plans/{mealPlan}', [ApiMealPlanController::class, 'destroy'])->name('api.v1.meal-plans.destroy');
        Route::post('meal-plans/{mealPlan}/regenerate/{slot}', [ApiMealPlanController::class, 'regenerateSlot'])->name('api.v1.meal-plans.regenerate');

        // Support
        Route::get('support', [\App\Http\Controllers\Api\V1\Support\SupportController::class, 'index']);
        Route::post('support', [\App\Http\Controllers\Api\V1\Support\SupportController::class, 'store']);
        Route::get('support/{reference}', [\App\Http\Controllers\Api\V1\Support\SupportController::class, 'show']);
        Route::post('support/{reference}/reply', [\App\Http\Controllers\Api\V1\Support\SupportController::class, 'reply']);

        // Grocery pricing (Kroger)
        Route::get('grocery/stores', [\App\Http\Controllers\Api\V1\Grocery\GroceryPriceController::class, 'stores']);
        Route::post('grocery/price', [\App\Http\Controllers\Api\V1\Grocery\GroceryPriceController::class, 'price']);

        // Admin impersonation (mobile)
        Route::post('admin/impersonate/{uuid}', [\App\Http\Controllers\Api\V1\Admin\ImpersonateController::class, 'start']);
    });
});
