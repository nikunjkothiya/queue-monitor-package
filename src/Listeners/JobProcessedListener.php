<?php

namespace NikunjKothiya\QueueMonitor\Listeners;

use Illuminate\Queue\Events\JobProcessed;

class JobProcessedListener
{
    public function handle(JobProcessed $event): void
    {
        // Reserved for future analytics (success count, duration, etc.)
    }
}


