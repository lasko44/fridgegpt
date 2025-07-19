<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SignupController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'] )->name('home');
Route::resource('recipe', \App\Http\Controllers\RecipeController::class)->only('store');
Route::get('/privacy-policy', function () {
    return inertia('PrivacyPolicy');
})->name('privacy-policy');
Route::get('/terms-of-service', function () {
    return inertia('TermsOfService');
})->name('terms-of-service');
Route::get('/cookie-policy', function () {
    return inertia('CookiePolicy');
})->name('cookie-policy');
Route::resource('signup', SignupController::class)->only('index', 'store');
Route::get('/auth/google', [SignupController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [SignupController::class, 'googleCallback']);
Route::get('/auth/facebook', [SignupController::class, 'facebookRedirect']);
Route::get('/auth/facebook/callback', [SignupController::class, 'facebookCallback']);

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');