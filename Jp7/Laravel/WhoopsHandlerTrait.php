<?php

namespace Jp7\Laravel;

use Exception;
use App;

trait WhoopsHandlerTrait
{
    protected function convertExceptionToResponse(Exception $e)
    {
        if (config('app.debug') && !App::runningInConsole() && config('app.env') !== 'production') { // just in case
            return $this->renderExceptionWithWhoops($e);
        }
        return parent::convertExceptionToResponse($e);
    }

    /**
     * Render an exception using Whoops.
     *
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     * @see https://mattstauffer.co/blog/bringing-whoops-back-to-laravel-5
     */
    protected function renderExceptionWithWhoops(Exception $e)
    {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());
        return $whoops->handleException($e);
    }
}
