<?php

namespace Jp7\Laravel;

use Illuminate\Queue\Events\JobProcessed;
use Throwable;
use Queue;
use Log;
use Request;

trait LogServiceProviderTrait
{
    protected function renameSyslogApp()
    {
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
            if ($url = env('QUEUE_HEARTBEAT_URL')) {
                get_headers($url);
            }
            $last = time();
        });
    }

    // Exceptions thrown or handled and logged with Log::error($e)
    protected function sentoToSentryAllExceptions()
    {
        Log::listen(function ($level, $message, $context) {
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
                    $sentry->captureException($message);
                } catch (Throwable $e) {
                    Log::critical($e);
                }
            }
        });
    }
}
