<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeIndexController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\Tokens\TokenCheckoutController;
use App\Http\Controllers\Tokens\TokenCodeController;
use App\Http\Controllers\Tokens\TokenController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariationController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Privacy and Terms of Service pages
Route::get('/privacy-policy', function () {
    return inertia('PrivacyPolicy');
})->name('privacy-policy');
Route::get('/terms-of-service', function () {
    return inertia('TermsOfService');
})->name('terms-of-service');
Route::get('/cookie-policy', function () {
    return inertia('CookiePolicy');
})->name('cookie-policy');

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/create', function () {
    $user = auth()->user();
    return \Inertia\Inertia::render('CreateRecipe', [
        'tokenBalance' => $user?->token_balance ?? 0,
        'tokenCost' => config('tokens.costs.recipe', 1),
    ]);
})->name('recipe.create');
Route::resource('recipe', RecipeController::class)->only('store', 'show');

// Stripe webhook (must be outside auth middleware, CSRF excluded by Cashier)
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('stripe.webhook');

// Authenticated routes
Route::group(['middleware' => 'auth'], function () {
    // Tokens
    Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');
    Route::get('/tokens/success', [TokenController::class, 'success'])->name('tokens.success');
    Route::post('/tokens/checkout', TokenCheckoutController::class)->name('tokens.checkout');
    Route::post('/tokens/redeem', TokenCodeController::class)->name('tokens.redeem');

    // Recipes & Variations
    Route::get('/recipes', RecipeIndexController::class)->name('recipes.index');
    Route::resource('variation', VariationController::class);

    // Payment method API
    Route::get('/api/payment-method', function () {
        $user = auth()->user();
        $pm = $user->defaultPaymentMethod();
        if (!$pm) {
            return response()->json(['last4' => null, 'brand' => null]);
        }
        return response()->json([
            'last4' => $pm->card->last4 ?? null,
            'brand' => ucfirst($pm->card->brand ?? 'Card'),
        ]);
    })->name('payment.method');

    Route::post('/api/setup-intent', function () {
        $user = auth()->user();
        $user->createOrGetStripeCustomer();
        return response()->json([
            'client_secret' => $user->createSetupIntent()->client_secret,
        ]);
    })->name('setup.intent');

    Route::post('/api/save-adjusted-recipe', function () {
        $data = request()->validate([
            'original_slug' => 'required|string|exists:recipes,slug',
            'servings' => 'required|integer|min:1|max:20',
            'ingredients' => 'required|array',
            'ingredients.*' => 'string',
            'nutrition' => 'nullable|array',
        ]);

        $original = \App\Models\Recipe::where('slug', $data['original_slug'])->firstOrFail();
        $user = auth()->user();

        // Create adjusted copy
        $adjusted = $user->recipe()->create([
            'name' => $original->name . ' (' . $data['servings'] . ' servings)',
            'slug' => \App\Facades\ModelSlugger::slug(\App\Models\Recipe::class, $original->name . ' ' . $data['servings'] . ' servings'),
            'description' => $original->description,
            'input_ingredients' => $original->input_ingredients,
            'is_variation' => true,
            'recipe_id' => $original->id,
            'servings' => $data['servings'],
            'calories_per_serving' => $data['nutrition']['calories'] ?? null,
            'protein_per_serving' => $data['nutrition']['protein'] ?? null,
            'carbs_per_serving' => $data['nutrition']['carbs'] ?? null,
            'fat_per_serving' => $data['nutrition']['fat'] ?? null,
            'nutrition_source' => $original->nutrition_source,
        ]);

        foreach ($data['ingredients'] as $ingredientText) {
            $adjusted->ingredients()->create(['name' => $ingredientText]);
        }

        return response()->json(['slug' => $adjusted->slug]);
    })->name('save.adjusted.recipe');

    Route::post('/api/save-payment-method', function () {
        $pm = request()->validate(['payment_method' => 'required|string']);
        $user = auth()->user();
        $user->addPaymentMethod($pm['payment_method']);
        $user->updateDefaultPaymentMethod($pm['payment_method']);
        return response()->json(['message' => 'Payment method saved.']);
    })->name('save.payment.method');

    // Account
    Route::get('/{user}/account', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/{user}/account', [UserController::class, 'update'])->name('user.update');
});

// Authentication routes
Route::get('/auth/google', [SignupController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [SignupController::class, 'googleCallback']);
Route::get('/auth/facebook', [SignupController::class, 'facebookRedirect']);
Route::get('/auth/facebook/callback', [SignupController::class, 'facebookCallback']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
Route::resource('signup', SignupController::class)->only('index', 'store');
Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

// Password reset routes
Route::resource('forgot-password', ForgotPasswordController::class)->only('index', 'store');
Route::get('reset-password/{token}', [ResetPasswordController::class, 'index'])->name('password.reset');
Route::post('reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
