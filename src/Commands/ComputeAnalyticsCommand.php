<?php

namespace NikunjKothiya\QueueMonitor\Commands;

use Illuminate\Console\Command;
use NikunjKothiya\QueueMonitor\Services\AnalyticsService;

class ComputeAnalyticsCommand extends Command
{
    protected $signature = 'queue-monitor:compute-analytics';

    protected $description = 'Compute and cache queue analytics metrics';

    public function handle(AnalyticsService $analytics): int
    {
        // For now, just touch the analytics so they can be cached externally if desired.
        $analytics->healthScore();
        $analytics->failuresOverTime();
        $analytics->topFailingJobs();

        $this->info('Queue analytics computed successfully.');

        return self::SUCCESS;
    }
}


