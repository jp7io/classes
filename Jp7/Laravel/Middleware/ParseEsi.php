<?php

namespace Jp7\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Router

class ParseEsi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $response = $next($request);
        if (!config('httpcache.esi')) {
            return $response;
        }
        $content = $response->getContent();
        if (str_contains($content, '<esi:include')) {
            preg_match_all('/<esi:include[^>]+src="(.+?)"[^>]+>/', $content, $matches);
            if ($matches) {
                foreach ($matches[0] as $i => $match) {
                    $subResponse = $this->getSubRequestResponse($matches[1][$i]);
                    $content = str_replace($match, $subResponse, $content);
                }
            }
            $response->setContent($content);
            $response->headers->remove('Content-Length');
            //$response->headers->remove('X-Content-Digest');
        }
        return $response;
    }

    protected function getSubRequestResponse($url)
    {
        $request = app(Request::class)->create($url, 'GET');
        $response = app(Router::class)->dispatch($request);
        return $response->getContent();
    }
}
