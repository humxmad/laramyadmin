<?php

namespace LaraMyAdmin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaraMyAdmin\LaraMyAdmin;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeLaraMyAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!LaraMyAdmin::check($request)) {
            abort(403, 'Unauthorized access to LaraMyAdmin.');
        }

        return $next($request);
    }
}
