<?php

namespace Jp7\HttpCache;

use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\SubRequestHandler;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpCache\SurrogateInterface;

/**
 * Adds four features to Symfony's HttpCache
 *
 * 1. Blacklist: don't cache entries that match 'httpcache.blacklist'
 * 2. Don't cache when theres old input (form responses)
 * 3. Invalidate: Customizable via 'httpcache.invalidate'
 * 4. Use stale cache on errors: 'httpcache.use_stale_on_errors'
 */
class HttpCacheExtension extends HttpCache
{
    private $kernel;

    public function __construct(HttpKernelInterface $kernel, StoreInterface $store, SurrogateInterface $surrogate = null, array $options = array())
    {
        $this->kernel = $kernel;
        parent::__construct($kernel, $store, $surrogate, $options);
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $blacklisted = $this->matchesBlacklist($request) || $request->old();
        if (!$blacklisted) {
            $request->headers->set('x-httpcache-cacheable', true);
        }
        if (!config('httpcache.enabled') || $blacklisted) {
            return SubRequestHandler::handle($this->kernel, $request, HttpKernelInterface::MASTER_REQUEST, $catch);
        }

        if (config('httpcache.invalidate')) {
            return $this->invalidate($request, $catch);
        }

        return parent::handle($request, $type, $catch);
    }

    public function matchesBlacklist(Request $request)
    {
        $blacklist = config('httpcache.blacklist');
        if (!$blacklist) {
            return false;
        }
        $pattern = '/^(' . addcslashes(implode('|', $blacklist), '/') . ').*/';
        return preg_match($pattern, $request->path());
    }
}
