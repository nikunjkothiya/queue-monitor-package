<?php

namespace NikunjKothiya\QueueMonitor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;

class PruneFailuresCommand extends Command
{
    protected $signature = 'queue-monitor:prune {--days=}';

    protected $description = 'Prune old queue failure records';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('queue-monitor.retention_days', 90));

        $cutoff = Carbon::now()->subDays($days);

        $count = QueueFailure::where('failed_at', '<', $cutoff)->delete();

        $this->info("Pruned {$count} queue failure records older than {$days} days.");

        return self::SUCCESS;
    }
}


