<?php

namespace App\Facades;

use App\Services\GuestService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool startGuestCache(string $ip)
 * @method static string getGuestCacheKey()
 */
class Guest extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GuestService::class;
    }
}