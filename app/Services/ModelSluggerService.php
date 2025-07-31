<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;


class ModelSluggerService
{

    private const COLUMNS = [
        'url_name',
        'slug'
    ];

    /**
     * @throws Exception
     */
    public function slug(string $model, string $string, string $columnName = null): string
    {
        $columnName = $columnName ?? self::inferColumn($model);
        $slug = Str::slug($string);

        return !self::slugExists($model, $slug, $columnName) ?
            $slug :
            $slug. '-'. Str::random(4);
    }

    /**
     * @throws Exception
     */
    private function inferColumn($model): string
    {
        $columns = Schema::getColumnListing(app($model)->getTable());

        foreach (ModelSluggerService::COLUMNS as $item){
            if(in_array($item, $columns)){
                return $item;
            }
        }

        throw new Exception('Cannot Infer Slug Column');
    }

    private function slugExists($model, string $slug, string $columnName): bool
    {
        return $model::query()->where($columnName, $slug)->count() > 0;
    }

}