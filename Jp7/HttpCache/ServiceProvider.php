<?php

namespace Jp7\HttpCache;

use Barryvdh\HttpCache\ServiceProvider as BaseServiceProvider;
use Barryvdh\StackMiddleware\StackMiddleware;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpCache\Esi;
use Symfony\Component\HttpKernel\HttpCache\Store as SymfonyStore;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $app = $this->app;
        $app->singleton(SymfonyStore::class, function ($app) {
            return new Store($app['http_cache.cache_dir']);
        });

        $this->app->make(StackMiddleware::class)->bind(CacheRequests::class,
            function($app) {
              return new HttpCacheExtension(
                  $app,
                  $this->app->make(StoreInterface::class),
                  $this->app->make(Esi::class),
                  $this->app['http_cache.options']
              );
            }
        );
    }
}
