<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\StripeController;

//Stripe payment routes
Route::post('create-setup-intent', [StripeController::class, 'store'])
    ->name('stripe.create-setup-intent');
