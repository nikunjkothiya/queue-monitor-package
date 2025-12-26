<?php

namespace NikunjKothiya\QueueMonitor\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;
use NikunjKothiya\QueueMonitor\Services\AnalyticsService;
use NikunjKothiya\QueueMonitor\Services\QueueDiagnosticsService;

class DashboardController extends Controller
{
    public function __construct(
        protected AnalyticsService $analytics,
        protected QueueDiagnosticsService $diagnostics
    ) {
    }

    public function index()
    {
        $days = 7;
        $from = Carbon::now()->subDays($days);

        $totalFailures = QueueFailure::where('failed_at', '>=', $from)->count();
        $unresolvedCount = QueueFailure::where('failed_at', '>=', $from)->unresolved()->count();
        $recentFailures = QueueFailure::recent()->get();

        $healthScore = $this->analytics->healthScore();
        $failuresOverTime = $this->analytics->failuresOverTime();
        $topFailingJobs = $this->analytics->topFailingJobs();

        $resolutionRate = $this->analytics->resolutionRate($days);
        $avgResolutionSeconds = $this->analytics->averageResolutionSeconds($days);

        $alertConfig = config('queue-monitor.alerts');
        $queueDiagnostics = $this->diagnostics->summarize();
        $autoRefreshSeconds = (int) config('queue-monitor.dashboard.auto_refresh_seconds', 10);

        return view('queue-monitor::dashboard.index', [
            'totalFailures' => $totalFailures,
            'unresolvedCount' => $unresolvedCount,
            'recentFailures' => $recentFailures,
            'healthScore' => $healthScore,
            'failuresOverTime' => $failuresOverTime,
            'topFailingJobs' => $topFailingJobs,
            'resolutionRate' => $resolutionRate,
            'avgResolutionSeconds' => $avgResolutionSeconds,
            'daysWindow' => $days,
            'alertConfig' => $alertConfig,
            'queueDiagnostics' => $queueDiagnostics,
            'autoRefreshSeconds' => $autoRefreshSeconds,
        ]);
    }
}


