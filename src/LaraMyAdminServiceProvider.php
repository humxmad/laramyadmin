<?php

namespace LaraMyAdmin;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LaraMyAdmin\Services\ConnectionManager;
use LaraMyAdmin\Services\DataService;
use LaraMyAdmin\Services\ExportImportService;
use LaraMyAdmin\Services\QueryService;
use LaraMyAdmin\Services\SchemaService;

class LaraMyAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laramyadmin.php', 'laramyadmin'
        );

        $this->app->singleton(ConnectionManager::class);
        $this->app->singleton(SchemaService::class);
        $this->app->singleton(DataService::class);
        $this->app->singleton(QueryService::class);
        $this->app->singleton(ExportImportService::class);
        $this->app->singleton('laramyadmin', function () {
            return new LaraMyAdmin();
        });
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerPublishing();
    }

    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('laramyadmin.path', 'laramyadmin'),
            'middleware' => config('laramyadmin.middleware', ['web']),
            'as' => 'laramyadmin.',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laramyadmin');
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laramyadmin.php' => config_path('laramyadmin.php'),
            ], 'laramyadmin-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/laramyadmin'),
            ], 'laramyadmin-views');
        }
    }
}
