<?php

namespace NikunjKothiya\QueueMonitor\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;

class RecurringFailureService
{
    /**
     * Get the threshold for marking failures as recurring.
     */
    protected function getThreshold(): int
    {
        return (int) config('queue-monitor.analytics.recurring_threshold', 3);
    }

    /**
     * Get the window (in hours) for counting recurring failures.
     */
    protected function getWindowHours(): int
    {
        return (int) config('queue-monitor.analytics.recurring_window_hours', 24);
    }

    /**
     * Detect and mark recurring failures.
     * This should be called periodically or after each new failure.
     */
    public function detectAndMarkRecurring(): int
    {
        $threshold = $this->getThreshold();
        $windowStart = Carbon::now()->subHours($this->getWindowHours());

        // Find group_hashes that have exceeded the threshold within the window
        $recurringHashes = QueueFailure::query()
            ->select('group_hash')
            ->selectRaw('COUNT(*) as occurrence_count')
            ->where('failed_at', '>=', $windowStart)
            ->whereNotNull('group_hash')
            ->groupBy('group_hash')
            ->havingRaw('COUNT(*) >= ?', [$threshold])
            ->pluck('group_hash');

        if ($recurringHashes->isEmpty()) {
            return 0;
        }

        // Mark all failures with these hashes as recurring
        $updated = QueueFailure::whereIn('group_hash', $recurringHashes)
            ->where('is_recurring', false)
            ->update(['is_recurring' => true]);

        return $updated;
    }

    /**
     * Get recurring failure patterns with their counts.
     */
    public function getRecurringPatterns(int $limit = 10): Collection
    {
        $windowStart = Carbon::now()->subHours($this->getWindowHours());

        return QueueFailure::query()
            ->select([
                'group_hash',
                'job_name',
                'exception_class',
                'file',
                'line',
            ])
            ->selectRaw('COUNT(*) as occurrence_count')
            ->selectRaw('MAX(failed_at) as last_occurrence')
            ->selectRaw('MIN(failed_at) as first_occurrence')
            ->selectRaw('SUM(CASE WHEN resolved_at IS NULL THEN 1 ELSE 0 END) as unresolved_count')
            ->where('failed_at', '>=', $windowStart)
            ->whereNotNull('group_hash')
            ->where('is_recurring', true)
            ->groupBy('group_hash', 'job_name', 'exception_class', 'file', 'line')
            ->orderByDesc('occurrence_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get count of recurring failure patterns.
     */
    public function getRecurringPatternCount(): int
    {
        return QueueFailure::query()
            ->select('group_hash')
            ->where('is_recurring', true)
            ->whereNotNull('group_hash')
            ->groupBy('group_hash')
            ->get()
            ->count();
    }

    /**
     * Get occurrence count for a specific failure pattern.
     */
    public function getOccurrenceCount(string $groupHash, int $hours = 24): int
    {
        return QueueFailure::query()
            ->where('group_hash', $groupHash)
            ->where('failed_at', '>=', Carbon::now()->subHours($hours))
            ->count();
    }

    /**
     * Get all failures for a specific pattern.
     */
    public function getPatternFailures(string $groupHash, int $limit = 50): Collection
    {
        return QueueFailure::query()
            ->where('group_hash', $groupHash)
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get summary statistics for recurring failures.
     */
    public function getSummary(): array
    {
        $threshold = $this->getThreshold();
        $windowStart = Carbon::now()->subHours($this->getWindowHours());

        $totalRecurring = QueueFailure::where('is_recurring', true)->count();
        $unresolvedRecurring = QueueFailure::where('is_recurring', true)
            ->whereNull('resolved_at')
            ->count();

        $patternCount = $this->getRecurringPatternCount();

        // Most common recurring job
        $mostCommonJob = QueueFailure::select('job_name')
            ->selectRaw('COUNT(*) as count')
            ->where('is_recurring', true)
            ->where('failed_at', '>=', $windowStart)
            ->groupBy('job_name')
            ->orderByDesc('count')
            ->first();

        return [
            'total_recurring_failures' => $totalRecurring,
            'unresolved_recurring' => $unresolvedRecurring,
            'pattern_count' => $patternCount,
            'most_common_job' => $mostCommonJob?->job_name,
            'most_common_count' => $mostCommonJob?->count ?? 0,
            'threshold' => $threshold,
            'window_hours' => $this->getWindowHours(),
        ];
    }
}
