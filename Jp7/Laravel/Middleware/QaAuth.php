<?php

namespace Jp7\Laravel\Middleware;

use Closure;
use App;

class QaAuth
{
    // Asks password for qa.* or alt.*
    public function handle($request, Closure $next)
    {
        if (!$this->isAuthorized($request)) {
            header('WWW-Authenticate: Basic realm="'.config('app.name').'"');
            header('HTTP/1.0 401 Unauthorized');
            echo '401 Unauthorized';
            exit;
        }

        return $next($request);
    }

    // We don't want super security
    // We just want to stop curious people and Google from opening the web page
    // While not complicating the development cycle
    protected function isAuthorized($request)
    {
        // QA or Alt environment
        if (!App::environment('staging') && !starts_with($request->getHttpHost(), 'alt.')) {
            return true;
        }
        // Allow API calls
        if ($request->ajax() || $request->wantsJson()) {
            return true;
        }
        // Allow PHP file_get_contents() calls
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }
        // Browser: HTTP authentication
        $name = config('app.name');
        return (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] === $name && $_SERVER['PHP_AUTH_PW'] === $name);
    }
}
