<?php

namespace Jp7\HttpCache;

use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if (!config('httpcache.enabled') || $this->matchesBlacklist($request) || $request->old()) {
            return $this->pass($request, $catch);
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

    /**
     * Validates that a cache entry is fresh.
     *
     * The original request is used as a template for a conditional
     * GET request with the backend.
     *
     * @param Request  $request A Request instance
     * @param Response $entry   A Response instance to validate
     * @param bool     $catch   Whether to process exceptions
     *
     * @return Response A Response instance
     */
    protected function validate(Request $request, Response $entry, $catch = false)
    {
        try {
            return parent::validate($request, $entry, $catch);
        } catch (\Throwable $e) {
            if (!config('httpcache.use_stale_on_errors')) {
                throw $e;
            }
            \Log::critical('[HTTPCACHE] Using stale cache because page could not be rendered');
            $entry = clone $entry;
            $entry->headers->remove('Date');
            $entry->setTtl(30); // try again in 30 seconds
            $this->store($request, $entry);
            return $entry;
        }
    }
}
