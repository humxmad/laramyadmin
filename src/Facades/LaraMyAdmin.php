<?php

namespace LaraMyAdmin\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void auth(\Closure $callback)
 * @method static bool check(\Illuminate\Http\Request $request)
 *
 * @see \LaraMyAdmin\LaraMyAdmin
 */
class LaraMyAdmin extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LaraMyAdmin\LaraMyAdmin::class;
    }
}
