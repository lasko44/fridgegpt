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
use App\Http\Controllers\MealPlan\MealPlanController;
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
Route::resource('recipe', RecipeController::class)->only('store', 'show', 'destroy');

// Stripe webhook (must be outside auth middleware, CSRF excluded by Cashier)
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('stripe.webhook');

// Authenticated routes
Route::group(['middleware' => 'auth'], function () {
    // Tokens
    Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');
    Route::get('/tokens/success', [TokenController::class, 'success'])->name('tokens.success');
    Route::post('/tokens/checkout', TokenCheckoutController::class)->name('tokens.checkout');
    Route::post('/tokens/redeem', TokenCodeController::class)->name('tokens.redeem');

    // Meal Plans
    Route::get('/meal-plans', [MealPlanController::class, 'index'])->name('meal-plans.index');
    Route::get('/meal-plans/create', [MealPlanController::class, 'create'])->name('meal-plans.create');
    Route::get('/meal-plans/pending', \App\Http\Controllers\Api\V1\MealPlan\PendingMealPlansController::class)->name('meal-plans.pending');
    Route::post('/meal-plans', [MealPlanController::class, 'store'])->name('meal-plans.store');
    Route::get('/meal-plans/{mealPlan}', [MealPlanController::class, 'show'])->name('meal-plans.show');
    Route::delete('/meal-plans/{mealPlan}', [MealPlanController::class, 'destroy'])->name('meal-plans.destroy');
    Route::post('/meal-plans/{mealPlan}/regenerate/{slot}', [MealPlanController::class, 'regenerateSlot'])->name('meal-plans.regenerate');

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

    // User support tickets
    Route::get('/support', [\App\Http\Controllers\Support\SupportConversationController::class, 'index'])->name('support.index');
    Route::get('/support/new', [\App\Http\Controllers\Support\SupportConversationController::class, 'create'])->name('support.create');
    Route::post('/support', [\App\Http\Controllers\Support\SupportConversationController::class, 'store'])->name('support.store');
    Route::get('/support/{conversation}', [\App\Http\Controllers\Support\SupportConversationController::class, 'show'])->name('support.show');
    Route::post('/support/{conversation}/reply', [\App\Http\Controllers\Support\SupportConversationController::class, 'reply'])->name('support.reply');

    // Notification inbox
    Route::get('/inbox', [\App\Http\Controllers\InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/unread-count', [\App\Http\Controllers\InboxController::class, 'unreadCount'])->name('inbox.unread-count');

    // Notification feed (poll endpoint for in-app toasts)
    Route::get('/notifications/feed', [\App\Http\Controllers\NotificationFeedController::class, 'user'])->name('notifications.feed');
    Route::get('/admin/notifications/feed', [\App\Http\Controllers\NotificationFeedController::class, 'admin'])
        ->middleware('admin')
        ->name('admin.notifications.feed');

    // Grocery pricing (Kroger)
    Route::get('/grocery/stores', [\App\Http\Controllers\GroceryController::class, 'stores'])->name('grocery.stores');
    Route::post('/grocery/price', [\App\Http\Controllers\GroceryController::class, 'price'])->name('grocery.price');
});

// Admin routes — protected by EnsureUserIsAdmin middleware (returns 404 for non-admins)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');

    // Users
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user:uuid}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user:uuid}/adjust-tokens', \App\Http\Controllers\Admin\AdjustTokenBalanceController::class)->name('users.adjust-tokens');
    Route::post('/users/{user:uuid}/toggle-admin', \App\Http\Controllers\Admin\ToggleAdminController::class)->name('users.toggle-admin');
    Route::post('/users/{user:uuid}/verify-email', \App\Http\Controllers\Admin\VerifyUserEmailController::class)->name('users.verify-email');
    Route::post('/users/{user:uuid}/impersonate', [\App\Http\Controllers\Admin\ImpersonateUserController::class, 'start'])->name('users.impersonate');

    // Tokens
    Route::get('/tokens/transactions', [\App\Http\Controllers\Admin\TokenTransactionController::class, 'index'])->name('tokens.transactions');
    Route::get('/tokens/codes', [\App\Http\Controllers\Admin\TokenCodeController::class, 'index'])->name('tokens.codes.index');
    Route::post('/tokens/codes', [\App\Http\Controllers\Admin\TokenCodeController::class, 'store'])->name('tokens.codes.store');
    Route::patch('/tokens/codes/{code}', [\App\Http\Controllers\Admin\TokenCodeController::class, 'update'])->name('tokens.codes.update');
    Route::delete('/tokens/codes/{code}', [\App\Http\Controllers\Admin\TokenCodeController::class, 'destroy'])->name('tokens.codes.destroy');

    // Content
    Route::get('/recipes', [\App\Http\Controllers\Admin\RecipeController::class, 'index'])->name('recipes.index');
    Route::delete('/recipes/{recipe}', [\App\Http\Controllers\Admin\RecipeController::class, 'destroy'])->name('recipes.destroy');
    Route::get('/meal-plans', [\App\Http\Controllers\Admin\MealPlanController::class, 'index'])->name('meal-plans.index');
    Route::delete('/meal-plans/{mealPlan}', [\App\Http\Controllers\Admin\MealPlanController::class, 'destroy'])->name('meal-plans.destroy');

    // System
    Route::get('/system', [\App\Http\Controllers\Admin\SystemController::class, 'index'])->name('system.index');
    Route::post('/system/jobs/{uuid}/retry', [\App\Http\Controllers\Admin\SystemController::class, 'retryFailed'])->name('system.retry');
    Route::delete('/system/jobs/{uuid}', [\App\Http\Controllers\Admin\SystemController::class, 'deleteFailed'])->name('system.delete-failed');
    Route::post('/system/retry-all', [\App\Http\Controllers\Admin\SystemController::class, 'retryAllFailed'])->name('system.retry-all');
    Route::post('/system/flush-failed', [\App\Http\Controllers\Admin\SystemController::class, 'flushFailed'])->name('system.flush-failed');

    // Messaging (broadcasts/announcements)
    Route::get('/messages', [\App\Http\Controllers\Admin\MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [\App\Http\Controllers\Admin\MessageController::class, 'store'])->name('messages.store');

    // Support inbox (two-way conversations)
    Route::get('/support', [\App\Http\Controllers\Admin\SupportConversationController::class, 'index'])->name('support.index');
    Route::get('/support/{conversation}', [\App\Http\Controllers\Admin\SupportConversationController::class, 'show'])->name('support.show');
    Route::post('/support/{conversation}/reply', [\App\Http\Controllers\Admin\SupportConversationController::class, 'reply'])->name('support.reply');
    Route::post('/support/{conversation}/status', [\App\Http\Controllers\Admin\SupportConversationController::class, 'updateStatus'])->name('support.status');
});

// Stop impersonating (must be outside admin middleware so impersonated user can call it)
Route::post('/stop-impersonating', [\App\Http\Controllers\Admin\ImpersonateUserController::class, 'stop'])
    ->middleware('auth')
    ->name('stop-impersonating');

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
