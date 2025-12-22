@extends('queue-monitor::layouts.app')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl border border-neutral-800 bg-neutral-900/70 px-4 py-3">
            <div class="flex items-center justify-between text-[11px] text-neutral-400">
                <span>Total Failures</span>
                <span class="inline-flex items-center text-[10px] text-neutral-500">
                    last {{ $daysWindow }} days
                </span>
            </div>
            <div class="mt-2 flex items-baseline space-x-2">
                <div class="text-2xl font-semibold text-neutral-50">{{ $totalFailures }}</div>
            </div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-neutral-900/70 px-4 py-3">
            <div class="flex items-center justify-between text-[11px] text-neutral-400">
                <span>Unresolved</span>
                <span class="inline-flex items-center text-red-400 text-[10px]">
                    requiring attention
                </span>
            </div>
            <div class="mt-2 flex items-baseline space-x-2">
                <div class="text-2xl font-semibold text-red-400">{{ $unresolvedCount }}</div>
            </div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-neutral-900/70 px-4 py-3">
            <div class="flex items-center justify-between text-[11px] text-neutral-400">
                <span>Resolution Rate</span>
                <span class="inline-flex items-center text-emerald-400 text-[10px]">
                    resolved in window
                </span>
            </div>
            <div class="mt-2 flex items-baseline space-x-2">
                <div class="text-2xl font-semibold text-emerald-400">
                    {{ number_format($resolutionRate, 1) }}%
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-neutral-900/70 px-4 py-3">
            <div class="flex items-center justify-between text-[11px] text-neutral-400">
                <span>Avg Resolution Time</span>
                <span class="inline-flex items-center text-[10px] text-neutral-500">
                    from failure to resolution
                </span>
            </div>
            <div class="mt-2 flex items-baseline space-x-2">
                @php
                    $avgInterval = $avgResolutionSeconds !== null ? \Carbon\CarbonInterval::seconds($avgResolutionSeconds)->cascade() : null;
                @endphp
                <div class="text-2xl font-semibold text-neutral-50">
                    @if ($avgInterval)
                        {{ $avgInterval->hours }}h {{ $avgInterval->minutes }}m
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6">
        <div class="text-center text-[11px] tracking-[0.25em] text-neutral-500 mb-3">
            QUEUE DRIVERS
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($queueDrivers as $driver)
                <div class="rounded-2xl border border-neutral-800 bg-neutral-900/80 px-4 py-5 flex flex-col justify-between">
                    <div class="text-sm font-semibold text-neutral-100">
                        {{ $driver['name'] }}
                    </div>
                    <div class="mt-1 text-[11px] text-neutral-500">
                        {{ $driver['subtitle'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-xl border border-neutral-800 bg-neutral-900/70 p-4 lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-semibold text-neutral-300">Failures Over Time</h2>
                <span class="text-[11px] text-neutral-500">Last {{ $daysWindow }} days</span>
            </div>
            <canvas id="failuresOverTimeChart" height="120"></canvas>
        </div>
        <div class="rounded-xl border border-neutral-800 bg-neutral-900/70 p-4">
            <h2 class="text-xs font-semibold text-neutral-300 mb-3">Top Failing Jobs</h2>
            <ul class="divide-y divide-neutral-800 text-xs">
                @forelse($topFailingJobs as $job)
                    <li class="py-2 flex justify-between">
                        <span class="truncate text-neutral-200" title="{{ $job->job_name }}">{{ $job->job_name }}</span>
                        <span class="font-mono text-neutral-400">{{ $job->count }}</span>
                    </li>
                @empty
                    <li class="py-2 text-neutral-500">No failures recorded yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-xl border border-neutral-800 bg-neutral-900/70 p-4 lg:col-span-1">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-xs font-semibold text-neutral-300">Queue Health Score</h2>
                    <p class="mt-1 text-[11px] text-neutral-500">
                        At-a-glance 0–100 health based on resolution rate, backlog, and recent failure trends.
                    </p>
                </div>
            </div>
            <div class="mt-4 flex items-center space-x-4">
                @php
                    $healthLabel = $healthScore >= 80 ? 'Healthy' : ($healthScore >= 50 ? 'Warning' : 'Critical');
                    $healthColor = $healthScore >= 80 ? 'bg-emerald-500/10 text-emerald-400 border-emerald-600/40' : ($healthScore >= 50 ? 'bg-amber-500/10 text-amber-300 border-amber-600/40' : 'bg-red-500/10 text-red-400 border-red-600/40');
                @endphp
                <div class="inline-flex flex-col items-start">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] border {{ $healthColor }}">
                        {{ $healthScore }}
                    </span>
                    <span class="mt-2 text-[11px] text-neutral-400">
                        {{ $healthLabel }}
                    </span>
                </div>
                <div class="text-[11px] text-neutral-500">
                    Higher scores mean fewer unresolved failures and faster resolution times.
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-800 bg-neutral-900/70 p-4 lg:col-span-1">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-xs font-semibold text-neutral-300">Smart Alert Throttling</h2>
                    <p class="mt-1 text-[11px] text-neutral-500">
                        Prevent alert fatigue with rate limiting per environment and failure burst detection.
                    </p>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2 text-[11px]">
                <span class="inline-flex items-center rounded-full bg-neutral-800 px-2 py-0.5 text-neutral-300">
                    {{ $alertConfig['throttle_minutes'] ?? 5 }} min cooldown
                </span>
                <span class="inline-flex items-center rounded-full bg-neutral-800 px-2 py-0.5 text-neutral-300">
                    Window: {{ $alertConfig['window_minutes'] ?? 5 }} min
                </span>
                <span class="inline-flex items-center rounded-full bg-neutral-800 px-2 py-0.5 text-neutral-300">
                    Min failures: {{ $alertConfig['min_failures_for_alert'] ?? 1 }}
                </span>
            </div>
            <p class="mt-3 text-[11px] text-neutral-500">
                Alerts fire only when failures cross the configured threshold inside the time window,
                and are suppressed until the cooldown expires.
            </p>
        </div>
    </div>

    <div class="rounded-xl border border-{{ $queueDiagnostics['status'] === 'ok' ? 'emerald-800' : ($queueDiagnostics['status'] === 'warning' ? 'amber-800' : 'red-800') }} bg-neutral-900/70 p-4 lg:col-span-1">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-xs font-semibold text-neutral-300">Queue Driver Diagnostics</h2>
                <p class="mt-1 text-[11px] text-neutral-500">
                    Checks that your default queue connection and driver are configured correctly.
                </p>
            </div>
        </div>
        <div class="mt-3 text-[11px] text-neutral-400 space-y-1">
            <div>
                <span class="font-semibold text-neutral-200">Default connection:</span>
                <span>{{ $queueDiagnostics['default'] ?? 'not set' }}</span>
            </div>
            <div>
                <span class="font-semibold text-neutral-200">Driver:</span>
                <span>{{ $queueDiagnostics['driver'] ?? 'unknown' }}</span>
            </div>
        </div>
        <ul class="mt-3 space-y-1 text-[11px]">
            @foreach ($queueDiagnostics['messages'] as $message)
                <li class="text-neutral-300">• {{ $message }}</li>
            @endforeach
        </ul>
    </div>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-neutral-800 bg-neutral-900/70 p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-semibold text-neutral-300">Projects</h2>
                <span class="text-[11px] text-neutral-500">Your monitored queues</span>
            </div>
            <ul class="space-y-3 text-xs">
                @foreach ($topFailingJobs as $job)
                    <li class="flex items-center justify-between">
                        <div>
                            <div class="text-neutral-200 truncate" title="{{ $job->job_name }}">{{ $job->job_name }}</div>
                            <div class="text-[11px] text-neutral-500">
                                {{ $job->count }} failures in last {{ $daysWindow }} days
                            </div>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-red-500/10 px-2 py-0.5 text-[11px] font-medium text-red-400">
                            Hot
                        </span>
                    </li>
                @endforeach
                @if ($topFailingJobs->isEmpty())
                    <li class="text-neutral-500 text-xs">No active failures. Queues look healthy.</li>
                @endif
            </ul>
        </div>

        <div class="rounded-xl border border-neutral-800 bg-neutral-900/70 p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-semibold text-neutral-300">Recent Failures</h2>
                <a href="{{ route('queue-monitor.failures.index') }}" class="text-[11px] text-neutral-400 hover:text-neutral-200">
                    View all
                </a>
            </div>
            <ul class="divide-y divide-neutral-800 text-xs">
                @forelse($recentFailures as $failure)
                    @php
                        $isResolved = $failure->isResolved();
                        $isNew = !$isResolved && $failure->failed_at && $failure->failed_at->gt(now()->subMinutes(30));
                        $statusLabel = $isResolved ? 'Resolved' : ($isNew ? 'New' : 'Requiring attention');
                        $statusClasses = $isResolved ? 'bg-emerald-500/10 text-emerald-400' : ($isNew ? 'bg-red-500/10 text-red-400' : 'bg-amber-500/10 text-amber-300');
                    @endphp
                    <li class="py-3 flex items-start justify-between space-x-3">
                        <div class="min-w-0">
                            <a href="{{ route('queue-monitor.failures.show', $failure) }}" class="text-neutral-100 font-medium hover:text-red-300">
                                {{ $failure->job_name }}
                            </a>
                            <div class="mt-0.5 text-[11px] text-neutral-400">
                                {{ \Illuminate\Support\Str::limit($failure->exception_message, 80) }}
                            </div>
                            <div class="mt-1 text-[11px] text-neutral-500">
                                {{ $failure->queue ?? 'default' }} • {{ $failure->environment }} •
                                {{ optional($failure->failed_at)->diffForHumans() }}
                            </div>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium {{ $statusClasses }}">
                            {{ $statusLabel }}
                        </span>
                    </li>
                @empty
                    <li class="py-4 text-neutral-500 text-xs">No recent failures.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <script>
        const failuresOverTimeLabels = @json($failuresOverTime->pluck('date'));
        const failuresOverTimeData = @json($failuresOverTime->pluck('count'));

        if (failuresOverTimeLabels.length) {
            const ctx = document.getElementById('failuresOverTimeChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: failuresOverTimeLabels,
                    datasets: [{
                        label: 'Failures',
                        data: failuresOverTimeData,
                        borderColor: 'rgb(248, 113, 113)',
                        backgroundColor: 'rgba(248, 113, 113, 0.08)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>
@endsection
