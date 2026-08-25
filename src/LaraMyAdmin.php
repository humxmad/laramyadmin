<?php

namespace LaraMyAdmin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LaraMyAdmin
{
    protected static ?Closure $authCallback = null;

    public static function auth(Closure $callback): void
    {
        static::$authCallback = $callback;
    }

    public static function check(Request $request): bool
    {
        if (static::$authCallback) {
            return (bool) call_user_func(static::$authCallback, $request);
        }

        $allowedEnvs = config('laramyadmin.allowed_environments', ['local', 'testing', 'development', 'staging', 'production']);
        return in_array(App::environment(), $allowedEnvs, true);
    }
}
