<?php

namespace Jp7\Laravel\Middleware;

use Closure;

/**
 * Avoids old() from validation errors from being cached in pages with a form
 * Must be added after \Barryvdh\HttpCache\Middleware\CacheRequests::class in the Kernel
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
