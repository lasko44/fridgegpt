<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariationController;
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

//Public routes
Route::get('/', [HomeController::class, 'index'] )->name('home');
Route::resource('recipe', RecipeController::class)->only('store','show');

Route::group(['middleware' => 'auth'], function () {
    Route::resource('subscription', SubscriptionController::class);
    Route::resource('variation', VariationController::class);
    Route::get('/{user}/account', [UserController::class, 'edit'])->name('user.edit');
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

