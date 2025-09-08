<?php

namespace App\Facades;

use App\Services\VariationService;
use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\VariationService
 * @method static array generate(array $data)
 */
class Variation extends Facade
{

    protected static function getFacadeAccessor(): string
    {
        return VariationService::class;
    }
}
