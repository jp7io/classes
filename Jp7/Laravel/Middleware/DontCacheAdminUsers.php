<?php

namespace Jp7\Laravel\Middleware;

use Closure;

/**
 * Invalidate cache to users logged in InterAdmin.
 * When an editor changes something waiting 5 minutes to see any change is too long.
 */
class DontCacheAdminUsers
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if (isset($_COOKIE['interadmin'])) {
            $response->setTtl(0);
        }
        return $response;
    }
}
