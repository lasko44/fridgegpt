<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\StripeController;

//Stripe payment routes
Route::post('create-payment-intent', [StripeController::class, 'store'])->name('stripe.create-payment-intent');
