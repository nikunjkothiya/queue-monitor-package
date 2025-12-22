<?php

namespace NikunjKothiya\QueueMonitor\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;

class AnalyticsService
{
    public function healthScore(): int
    {
        $total = QueueFailure::count();
        if ($total === 0) {
            return 100;
        }

        $unresolved = QueueFailure::unresolved()->count();

        $score = 100 - ($unresolved / max($total, 1) * 60);

        $recentWindow = Carbon::now()->subDays(7);
        $recentFailures = QueueFailure::where('failed_at', '>=', $recentWindow)->count();

        if ($recentFailures > 0) {
            $score -= min(40, $recentFailures * 2);
        }

        return (int) max(0, min(100, round($score)));
    }

    public function resolutionRate(int $days = 7): float
    {
        $from = Carbon::now()->subDays($days);

        $total = QueueFailure::where('failed_at', '>=', $from)->count();
        if ($total === 0) {
            return 100.0;
        }

        $resolved = QueueFailure::where('failed_at', '>=', $from)
            ->whereNotNull('resolved_at')
            ->count();

        return round(($resolved / $total) * 100, 1);
    }

    public function averageResolutionSeconds(int $days = 7): ?int
    {
        $from = Carbon::now()->subDays($days);

        $resolved = QueueFailure::where('failed_at', '>=', $from)
            ->whereNotNull('resolved_at')
            ->get(['failed_at', 'resolved_at']);

        if ($resolved->isEmpty()) {
            return null;
        }

        $totalSeconds = $resolved->reduce(function (int $carry, QueueFailure $failure): int {
            return $carry + $failure->failed_at->diffInSeconds($failure->resolved_at);
        }, 0);

        return (int) floor($totalSeconds / max(1, $resolved->count()));
    }

    public function failuresOverTime(int $days = 30): Collection
    {
        $from = Carbon::now()->subDays($days);

        return QueueFailure::select(
            DB::raw('DATE(failed_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('failed_at', '>=', $from)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function topFailingJobs(int $limit = 5): Collection
    {
        return QueueFailure::select('job_name', DB::raw('COUNT(*) as count'))
            ->groupBy('job_name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }
}


