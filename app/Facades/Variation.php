y<?php

namespace App\Facades;

use App\Services\VariationService;
use Illuminate\Support\Facades\Facade;

class Variation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return VariationService::class;
    }
}
