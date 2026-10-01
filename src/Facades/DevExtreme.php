<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel\Facades;

use DevExtreme\Data\Contracts\DataSourceInterface;
use DevExtreme\Data\Laravel\DevExtremeLoader;
use DevExtreme\Data\LoadOptions;
use DevExtreme\Data\LoadResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @method static LoadOptions options(?Request $request = null)
 * @method static LoadResult load(mixed $source, ?Request $request = null)
 * @method static JsonResponse response(mixed $source, ?Request $request = null)
 * @method static DataSourceInterface source(mixed $source)
 *
 * @see DevExtremeLoader
 */
final class DevExtreme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DevExtremeLoader::class;
    }
}
