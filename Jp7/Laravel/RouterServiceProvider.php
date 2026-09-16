<?php

namespace Jp7\Laravel;

use Illuminate\Support\ServiceProvider;

class RouterServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Used by Jp7\Laravel\RouterFacade
        \App::singleton(Router::class, function (array $app): \Jp7\Laravel\Router {
            return new Router($app['router']);
        });
    }
}
