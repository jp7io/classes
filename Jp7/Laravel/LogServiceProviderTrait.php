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
        if (version_compare(app()->version(), '5.6.0') >= 0) {
            throw new \UnexpectedValueException('For Laravel 5.6+ remove calls to sentoToSentryAllExceptions(), add "sentry" to config/logging.php, and use the stack option.');
        }
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

    /**
     * Keep logs on hacking attempts. Better safe than sorry.
     */
    public function logPossibleAttacks($logLevel = 'error')
    {
        if (!$_GET) {
            return;
        }
        foreach ($_GET as $key => $value) {
            if (str_contains($value, '<') || // Tags: XSS
                str_contains($value, '&#') || // Entities: XSS
                str_contains($value, '\\') || // Escape: XSS or SQL
                str_contains($value, '/*') || // Comments: SQL Injection
                str_contains($value, ';') || // End statement: SQL Injection
                str_contains($value, '\'') || // Quotes: SQL Injection
                preg_match('/\b(SELECT|INSERT|DROP|UPDATE|EXEC|DECLARE|ORDER BY|HAVING)\b/i', $value) || // Operations: SQL Injection
                preg_match('/\w+\s*\(.*(\(.+\)|[^\w\d\s,()-]).*\)/s', $value) || // Function call: XSS or SQL
                preg_match('/[^\\p{Latin}\x{0020}-\x{00FF}]/u', $value) // UTF-8 non-latin characters
            ) {
                Log::$logLevel(new \UnexpectedValueException("[HACKING] Possible attack attempt: $key=$value"));
            }
        }
    }
}
