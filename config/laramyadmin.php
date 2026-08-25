<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LaraMyAdmin Route Path
    |--------------------------------------------------------------------------
    |
    | The URI prefix where LaraMyAdmin will be accessible in the browser.
    | e.g. 'laramyadmin' -> http://your-app.test/laramyadmin
    |
    */
    'path' => env('LARAMYADMIN_PATH', 'laramyadmin'),

    /*
    |--------------------------------------------------------------------------
    | LaraMyAdmin Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every LaraMyAdmin route, giving you
    | the chance to add authentication or custom gate checks.
    |
    */
    'middleware' => [
        'web',
        \LaraMyAdmin\Http\Middleware\AuthorizeLaraMyAdmin::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Read Only Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, destructive queries (DROP, TRUNCATE, DELETE, UPDATE, ALTER)
    | and data inserts will be blocked.
    |
    */
    'read_only' => env('LARAMYADMIN_READ_ONLY', false),

    /*
    |--------------------------------------------------------------------------
    | Allow Dynamic Connections
    |--------------------------------------------------------------------------
    |
    | Allow users to connect to new databases on the fly by providing host,
    | port, credentials, and database name through the UI.
    |
    */
    'allow_dynamic_connections' => env('LARAMYADMIN_ALLOW_DYNAMIC_CONNECTIONS', true),

    /*
    |--------------------------------------------------------------------------
    | Default Query Limit
    |--------------------------------------------------------------------------
    |
    | The default row limit returned in browse mode and custom SQL runner.
    |
    */
    'default_limit' => 250,

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments
    |--------------------------------------------------------------------------
    |
    | Environments where LaraMyAdmin can run. For production, define an
    | authorization gate in your AppServiceProvider (LaraMyAdmin::auth(...)).
    |
    */
    'allowed_environments' => ['local', 'testing', 'development', 'staging', 'production'],
];
