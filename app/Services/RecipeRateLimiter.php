<?php
// app/Services/RecipeRateLimiter.php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

class RecipeRateLimiter
{
    protected int $maxAttempts;
    protected int $decaySeconds;

    public function __construct(int $maxAttempts = 1, int $decaySeconds = 15)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    public function tooManyAttempts(string $ip): bool
    {
        $key = $this->key($ip);
        return RateLimiter::tooManyAttempts($key, $this->maxAttempts);
    }

    public function hit(string $ip): void
    {
        $key = $this->key($ip);
        RateLimiter::hit($key, $this->decaySeconds);
    }

    public function availableIn(string $ip): int
    {
        $key = $this->key($ip);
        return RateLimiter::availableIn($key);
    }

    protected function key(string $ip): string
    {
        return 'recipe-store:' . $ip;
    }
}