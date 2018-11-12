<?php

namespace Jp7\Laravel\Middleware;

use Closure;

/**
 * Avoids old() form validation errors from being cached in pages with a form.
 * Must be added after \Barryvdh\HttpCache\Middleware\CacheRequests::class in the Kernel.
 * It was first added because https://www.abipe.org.br/ has a login form, but we can't remove the cache from the home page.
 * Could have been solved with AJAX or an iframe, but we don't know how many other pages are affected by this.
 */
class DontCacheOldInput
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if (old()) {
            $response->setTtl(0); // Don't cache validation errors
        }
        return $response;
    }
}
