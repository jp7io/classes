<?php

namespace Jp7\Laravel;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Log\Events\MessageLogged;
use Throwable;
use Queue;
use Log;
use Request;

trait LogServiceProviderTrait
{
    /**
     * @deprecated not needed for Laravel 5.3+
     */
    protected function renameSyslogApp()
    {
        if (!config('app.log')) {
            throw new \UnexpectedValueException('No config(app.log) value. For Laravel 5.3+ remove calls to renameSyslogApp()');
        }
        if (config('app.log') === 'syslog') {
            Log::getMonolog()->popHandler();
            Log::useSyslog(config('app.name'));
        }
    }

    protected function listenQueueEvents()
    {
        Queue::after(function (JobProcessed $event) {
            Log::info('[QUEUE] Processed: '.$event->job->getName());
        });
        Queue::looping(function () {
            static $last = 0;
            if ($last > time() - 60) {
                return; // too soon for ping
            }
            Log::info('[QUEUE] Ping');
            $last = time();
        });
    }

    // Exceptions thrown or handled and logged with Log::error($e)
    protected function sentoToSentryAllExceptions($extra_context = [])
    {
        $logHandler = function ($level, $message, $context) use ($extra_context) {
            if ($level === 'error' && $message instanceof Throwable) {
                try {
                    // might fail if app() has broken bindings
                    $sentry = app('sentry');
                    $context = [
                        'ip' => Request::ip()
                    ];
                    // might fail if auth() has wrong settings
                    if ($user = auth()->user()) {
                        $context['id'] = $user->id ?? 0;
                        $context['email'] = $user->email ?? '';
                    }
                    $sentry->user_context($context);
                    if ($extra_context) {
                        $sentry->extra_context(value($extra_context));
                    }
                    $sentry->captureException($message);
                } catch (Throwable $e) {
                    Log::critical($e);
                }
            }
        };

        if (class_exists(MessageLogged::class)) { // Laravel 5.4+
            Log::listen(function (MessageLogged $message) use ($logHandler) {
                if ($message->level === 'error') {
                    $exception = $message->message instanceof Throwable ?
                        $message->message :
                        $message->context['exception'];
                    unset($message->context['exception']);
                    $logHandler($message->level, $exception, $message->context);
                }
            });
        } else { // Laravel 5.3-
            Log::listen($logHandler);
        }
    }
}
