<?php

namespace NikunjKothiya\QueueMonitor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;

/**
 * Compute analytics in the background to avoid impacting real-time performance.
 * 
 * This command should be scheduled to run periodically (e.g., every 5 minutes)
 * to update recurring failure flags and compute aggregate statistics.
 */
class ComputeAnalyticsCommand extends Command
{
    protected $signature = 'queue-monitor:compute-analytics 
                            {--sync-recurring : Sync recurring flags from cache to database}
                            {--rebuild-cache : Rebuild cache counters from database}
                            {--compute-stats : Compute and cache aggregate statistics}';

    protected $description = 'Compute queue monitor analytics in the background';

    public function handle(): int
    {
        $startTime = microtime(true);
        
        if ($this->option('sync-recurring') || !$this->hasAnyOption()) {
            $this->syncRecurringFlags();
        }
        
        if ($this->option('rebuild-cache')) {
            $this->rebuildCacheCounters();
        }
        
        if ($this->option('compute-stats') || !$this->hasAnyOption()) {
            $this->computeAggregateStats();
        }
        
        $elapsed = round((microtime(true) - $startTime) * 1000, 2);
        $this->info("Analytics computation completed in {$elapsed}ms");
        
        return self::SUCCESS;
    }
    
    protected function hasAnyOption(): bool
    {
        return $this->option('sync-recurring') || 
               $this->option('rebuild-cache') || 
               $this->option('compute-stats');
    }
    
    /**
     * Sync recurring flags from cache counters to database.
     * This is more efficient than querying on every failure.
     */
    protected function syncRecurringFlags(): void
    {
        $this->info('Syncing recurring flags...');
        
        $threshold = config('queue-monitor.analytics.recurring_threshold', 3);
        $windowHours = config('queue-monitor.analytics.recurring_window_hours', 24);
        $windowStart = Carbon::now()->subHours($windowHours);
        $batchSize = config('queue-monitor.performance.analytics_batch_size', 1000);
        
        // Find group hashes that exceed threshold
        $recurringHashes = QueueFailure::query()
            ->select('group_hash')
            ->selectRaw('COUNT(*) as cnt')
            ->where('failed_at', '>=', $windowStart)
            ->whereNotNull('group_hash')
            ->groupBy('group_hash')
            ->havingRaw('COUNT(*) >= ?', [$threshold])
            ->pluck('group_hash');
        
        if ($recurringHashes->isEmpty()) {
            $this->line('  No recurring patterns found.');
            return;
        }
        
        // Batch update to avoid locking
        $updated = 0;
        foreach ($recurringHashes->chunk($batchSize) as $chunk) {
            $count = QueueFailure::whereIn('group_hash', $chunk)
                ->where('is_recurring', false)
                ->update(['is_recurring' => true]);
            $updated += $count;
        }
        
        // Also mark non-recurring (cleanup old flags)
        $cleanedUp = QueueFailure::whereNotIn('group_hash', $recurringHashes)
            ->where('is_recurring', true)
            ->where('failed_at', '>=', $windowStart)
            ->update(['is_recurring' => false]);
        
        $this->line("  Marked {$updated} failures as recurring, cleaned up {$cleanedUp}");
    }
    
    /**
     * Rebuild cache counters from database.
     * Useful after cache flush or for accuracy verification.
     */
    protected function rebuildCacheCounters(): void
    {
        $this->info('Rebuilding cache counters...');
        
        $windowHours = config('queue-monitor.analytics.recurring_window_hours', 24);
        $windowStart = Carbon::now()->subHours($windowHours);
        
        $counts = QueueFailure::query()
            ->select('group_hash')
            ->selectRaw('COUNT(*) as cnt')
            ->where('failed_at', '>=', $windowStart)
            ->whereNotNull('group_hash')
            ->groupBy('group_hash')
            ->get();
        
        $cacheDriver = config('queue-monitor.performance.cache_driver');
        $cache = $cacheDriver ? Cache::store($cacheDriver) : Cache::store();
        
        foreach ($counts as $row) {
            $key = "qm:count:{$row->group_hash}";
            $cache->put($key, $row->cnt, now()->addHours($windowHours));
        }
        
        $this->line("  Rebuilt counters for {$counts->count()} groups");
    }
    
    /**
     * Compute and cache aggregate statistics for dashboard.
     */
    protected function computeAggregateStats(): void
    {
        $this->info('Computing aggregate statistics...');
        
        $cacheDriver = config('queue-monitor.performance.cache_driver');
        $cache = $cacheDriver ? Cache::store($cacheDriver) : Cache::store();
        $cacheTtl = now()->addMinutes(5);
        
        // Today's stats
        $today = Carbon::today();
        $todayStats = QueueFailure::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN resolved_at IS NULL THEN 1 ELSE 0 END) as unresolved')
            ->selectRaw('SUM(CASE WHEN is_recurring = 1 THEN 1 ELSE 0 END) as recurring')
            ->selectRaw('SUM(CASE WHEN priority_score >= 80 THEN 1 ELSE 0 END) as critical')
            ->where('failed_at', '>=', $today)
            ->first();
        
        $cache->put('qm:stats:today', [
            'total' => $todayStats->total ?? 0,
            'unresolved' => $todayStats->unresolved ?? 0,
            'recurring' => $todayStats->recurring ?? 0,
            'critical' => $todayStats->critical ?? 0,
            'computed_at' => now()->toIso8601String(),
        ], $cacheTtl);
        
        // Last 7 days trend
        $weekAgo = Carbon::now()->subDays(7);
        $dailyTrend = QueueFailure::query()
            ->selectRaw('DATE(failed_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->where('failed_at', '>=', $weekAgo)
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
        
        $cache->put('qm:stats:daily_trend', $dailyTrend, $cacheTtl);
        
        // Top failing jobs
        $topJobs = QueueFailure::query()
            ->select('job_name')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('MAX(priority_score) as max_priority')
            ->where('failed_at', '>=', $weekAgo)
            ->groupBy('job_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
        
        $cache->put('qm:stats:top_jobs', $topJobs, $cacheTtl);
        
        // Queue distribution
        $queueDist = QueueFailure::query()
            ->select('queue')
            ->selectRaw('COUNT(*) as count')
            ->where('failed_at', '>=', $weekAgo)
            ->whereNotNull('queue')
            ->groupBy('queue')
            ->orderByDesc('count')
            ->pluck('count', 'queue')
            ->toArray();
        
        $cache->put('qm:stats:queue_distribution', $queueDist, $cacheTtl);
        
        // Health score
        $healthScore = $this->computeHealthScore();
        $cache->put('qm:stats:health_score', $healthScore, $cacheTtl);
        
        $this->line('  Cached: today stats, daily trend, top jobs, queue distribution, health score');
    }
    
    /**
     * Compute health score (0-100).
     */
    protected function computeHealthScore(): int
    {
        $total = QueueFailure::count();
        
        if ($total === 0) {
            return 100;
        }
        
        $score = 100;
        
        // Deduct for unresolved failures
        $unresolved = QueueFailure::whereNull('resolved_at')->count();
        $unresolvedRatio = $unresolved / max($total, 1);
        $score -= (int) ($unresolvedRatio * 40);
        
        // Deduct for recent failures (last 24h)
        $recentCount = QueueFailure::where('failed_at', '>=', Carbon::now()->subDay())->count();
        $score -= min(30, $recentCount * 2);
        
        // Deduct for critical failures
        $criticalCount = QueueFailure::where('priority_score', '>=', 80)
            ->whereNull('resolved_at')
            ->count();
        $score -= min(20, $criticalCount * 5);
        
        // Deduct for recurring patterns
        $recurringCount = QueueFailure::where('is_recurring', true)
            ->whereNull('resolved_at')
            ->count();
        $score -= min(10, $recurringCount * 2);
        
        return max(0, min(100, $score));
    }
}
