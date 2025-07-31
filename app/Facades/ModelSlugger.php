<?php

namespace App\Facades;

use App\Services\ModelSluggerService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string slug(string $model, string $string, string $columnName = null)
 */
class ModelSlugger extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return ModelSluggerService::class;
    }

}