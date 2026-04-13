<?php

namespace App\Providers;

use App\Ssr\TimeoutHttpGateway;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Inertia\Ssr\Gateway;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Gateway::class, TimeoutHttpGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share('appName', config('app.name'));
        Cashier::calculateTaxes();
    }
}
