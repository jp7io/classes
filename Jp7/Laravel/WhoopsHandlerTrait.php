<?php

namespace Jp7\Laravel;

use App;
use Throwable;

trait WhoopsHandlerTrait
{
    protected function convertExceptionToResponse(Throwable $e)
    {
        if (config('app.debug')) {
            if (config('app.env') !== 'local' || App::runningInConsole()) {
                // dont use whoops in production or on console operations
                return parent::convertExceptionToResponse($e);
            }
            return $this->renderExceptionWithWhoops($e);
        }
        // Custom error 500 page
        // Laravel only handles custom pages when it's a HttpException
        return response()->view('errors.500', [], 500);
    }

    /**
     * Render an exception using Whoops.
     *
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     * @see https://mattstauffer.co/blog/bringing-whoops-back-to-laravel-5
     */
    protected function renderExceptionWithWhoops(Throwable $e)
    {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
        return $whoops->handleException($e);
    }
}
