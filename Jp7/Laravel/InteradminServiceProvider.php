<?php

namespace Jp7\Laravel;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\ServiceProvider;
use Jp7\InterAdmin\Schema\DynamicLoader;
use Jp7\InterAdmin\Schema\TypeCache;
use Jp7\Laravel\RouterFacade as r;
use Schema;
use App;
use PDOException;
use Log;
use View;
use Route;
use DB;
use Cache;

/*
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Jp7\ExceptionHandler;
*/

class InteradminServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->isDownForMaintenance()) {
            return;
        }
        if (isset($this->app['view'])) {
            $this->shareViewPath();
        }

        CacheExtension::apply();

        $this->publishPackageFiles();
        $this->bootOrm();
        // `queue:work` boots once for every job it runs, so what the models derived from `types`
        // would otherwise outlive a type edited in the admin until the worker restarts.
        $this->app['events']->listen(JobProcessing::class, fn () => \InterAdmin\Models\Type::forgetTypeState());
        // self::bootTestingEnv();
    }

    private function publishPackageFiles(): void
    {
        $base = __DIR__.'/../..';

        $this->publishes([
            $base.'/config/imgix.php' => config_path('imgix.php'),
            $base.'/config/interadmin.php' => config_path('interadmin.php'),
        ], 'config');

        $this->publishes([
            $base.'/resources/lang/en/interadmin.php' => resource_path('lang/en/interadmin.php'),
            $base.'/resources/lang/pt-BR/interadmin.php' => resource_path('lang/pt-BR/interadmin.php'),
        ], 'resources');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $currentType = function () {
            $route = Route::getCurrentRoute();
            if ($route) { // Some pages don't have route, like 503.blade.php
                return r::getTypeByRoute($route);
            }
        };
        App::bind(\InterAdmin\Models\Type::class, $currentType);
    }

    private function bootOrm(): void
    {
        if (config('interadmin.namespace')) {
            \InterAdmin\Models\Type::setDefaultClass(config('interadmin.namespace').'Type');
        }
        DynamicLoader::register();
        // The unit's stamp check, here and not inside its first query-counted read of a type.
        TypeCache::store();
    }

    private function shareViewPath(): void
    {
        View::composer('*', function ($view): void {
            $parts = explode('.', $view->getName());
            array_pop($parts);
            View::share('viewPath', implode('.', $parts));
        });
    }

    /*
    private function bootTestingEnv()
    {
        if (\App::environment('testing')) {
            // Filters are disabled by default
            \Route::enableFilters();

            // Bug former with phpunit
            if (!\Request::hasSession()) {
                \Request::setSession(\App::make('session.store'));
            }
        }
    }
    */
    /*
    private function extendFormer()
    {
        \App::before(function ($request) {
            // Needed for tests
            \Former::getFacadeRoot()->ids = [];
        });
    }
    */
}
