<?php

namespace App\Services;


use App\Models\GuestSession;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GuestService
{
    private ?string $guestCacheKey = null;

    public function startGuestCache(string $ip): bool
    {
        if($this->checkRate($ip)){
            $cacheKey = $this->createGuestCacheKey();
            $cacheData = [
                'ip_address' => $ip,
                'recipes' => [],
            ];
            Cache::put($cacheKey, $cacheData, now()->addDay());

            $this->storeGuestSession($cacheKey, $ip);

            return true;
        }
        return false;
    }

    public function getGuestCacheKey(): string
    {
        return $this->guestCacheKey;
    }

    private function createGuestCacheKey(): string
    {
        $cacheKey = 'guest_' . Str::uuid();
        $this->guestCacheKey = $cacheKey;

        return $cacheKey;
    }

    private function checkRate($ip): bool
    {
        $rateLimiter = new RecipeRateLimiter();

        if ($rateLimiter->tooManyAttempts($ip)) {
            throw new Exception('Too many attempts.', 429);
        }
        $rateLimiter->hit($ip);

        return true;
    }

    private function storeGuestSession($cacheKey, $ip): void
    {
        GuestSession::create([
            'ip_address' => $ip,
            'guest_cache_key' => $cacheKey,
        ]);
    }

}