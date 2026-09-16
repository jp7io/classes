<?php

namespace Jp7\Laravel;

use Illuminate\Queue\Events\JobProcessed;
use Queue;
use Log;

trait LogServiceProviderTrait
{
    protected function listenQueueEvents()
    {
        Queue::after(function (JobProcessed $event): void {
            Log::info('[QUEUE] Processed: '.$event->job->getName());
        });
        Queue::looping(function (): void {
            static $last = 0;
            if ($last > time() - 60) {
                return; // too soon for ping
            }
            Log::info('[QUEUE] Ping');
            $last = time();
        });
    }
}
