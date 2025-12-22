@extends('queue-monitor::layouts.app')

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body small">
                    <div class="d-flex justify-content-between text-muted">
                        <span>Total Failures</span>
                        <span>last {{ $daysWindow }} days</span>
                    </div>
                    <h3 class="mt-2 mb-0">{{ $totalFailures }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body small">
                    <div class="d-flex justify-content-between text-muted">
                        <span>Unresolved</span>
                        <span>requiring attention</span>
                    </div>
                    <h3 class="mt-2 mb-0 text-danger">{{ $unresolvedCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body small">
                    <div class="d-flex justify-content-between text-muted">
                        <span>Resolution Rate</span>
                        <span>resolved in window</span>
                    </div>
                    <h3 class="mt-2 mb-0 text-success">
                        {{ number_format($resolutionRate, 1) }}%
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body small">
                    <div class="d-flex justify-content-between text-muted">
                        <span>Avg Resolution Time</span>
                        <span>from failure to resolution</span>
                    </div>
                    @php
                        $avgInterval = $avgResolutionSeconds !== null ? \Carbon\CarbonInterval::seconds($avgResolutionSeconds)->cascade() : null;
                    @endphp
                    <h3 class="mt-2 mb-0">
                        @if ($avgInterval)
                            {{ $avgInterval->hours }}h {{ $avgInterval->minutes }}m
                        @else
                            —
                        @endif
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3 text-center text-uppercase small text-muted">
        Queue Drivers
    </div>
    <div class="row g-3 mb-4">
        @foreach ($queueDrivers as $driver)
            <div class="col-6 col-lg-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-body small">
                        <div class="fw-semibold">{{ $driver['name'] }}</div>
                        <div class="text-muted">{{ $driver['subtitle'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center small text-muted">
                    <span>Failures Over Time</span>
                    <span>Last {{ $daysWindow }} days</span>
                </div>
                <div class="card-body">
                    <div style="height:260px">
                        <canvas id="failuresOverTimeChart" class="w-100 h-100"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header small">
                    Top Failing Jobs
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small mb-0">
                        @forelse($topFailingJobs as $job)
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-truncate" title="{{ $job->job_name }}">{{ $job->job_name }}</span>
                                <span class="text-muted">{{ $job->count }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No failures recorded yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header small">
                    Queue Health Score
                </div>
                <div class="card-body small">
                    @php
                        $healthLabel = $healthScore >= 80 ? 'Healthy' : ($healthScore >= 50 ? 'Warning' : 'Critical');
                        $badgeClass = $healthScore >= 80 ? 'bg-success' : ($healthScore >= 50 ? 'bg-warning text-dark' : 'bg-danger');
                    @endphp
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge {{ $badgeClass }} rounded-pill fs-6">
                            {{ $healthScore }}
                        </span>
                        <div>
                            <div class="fw-semibold">{{ $healthLabel }}</div>
                            <div class="text-muted">
                                Higher scores mean fewer unresolved failures and faster resolution times.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header small">
                    Smart Alert Throttling
                </div>
                <div class="card-body small">
                    <p class="text-muted mb-2">
                        Prevent alert fatigue with rate limiting per environment and failure burst detection.
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge bg-secondary-subtle text-secondary">
                            {{ $alertConfig['throttle_minutes'] ?? 5 }} min cooldown
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary">
                            Window: {{ $alertConfig['window_minutes'] ?? 5 }} min
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary">
                            Min failures: {{ $alertConfig['min_failures_for_alert'] ?? 1 }}
                        </span>
                    </div>
                    <p class="text-muted mb-0">
                        Alerts fire only when failures cross the configured threshold inside the time window,
                        and are suppressed until the cooldown expires.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header small">
                    Queue Driver Diagnostics
                </div>
                <div class="card-body small">
                    <div class="mb-2">
                        <strong>Default connection:</strong> {{ $queueDiagnostics['default'] ?? 'not set' }}
                    </div>
                    <div class="mb-2">
                        <strong>Driver:</strong> {{ $queueDiagnostics['driver'] ?? 'unknown' }}
                    </div>
                    <ul class="mb-0 ps-3 text-muted">
                        @foreach ($queueDiagnostics['messages'] as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header small d-flex justify-content-between align-items-center">
                    <span>Projects</span>
                    <span class="text-muted">Your monitored queues</span>
                </div>
                <div class="card-body small">
                    <ul class="list-group list-group-flush mb-0">
                        @foreach ($topFailingJobs as $job)
                            <li class="list-group-item d-flex justify-content-between">
                                <div>
                                    <div class="fw-semibold text-truncate" title="{{ $job->job_name }}">{{ $job->job_name }}</div>
                                    <div class="text-muted">
                                        {{ $job->count }} failures in last {{ $daysWindow }} days
                                    </div>
                                </div>
                                <span class="badge bg-danger-subtle text-danger">Hot</span>
                            </li>
                        @endforeach
                        @if ($topFailingJobs->isEmpty())
                            <li class="list-group-item text-muted">No active failures. Queues look healthy.</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header small d-flex justify-content-between align-items-center">
                    <span>Recent Failures</span>
                    <a href="{{ route('queue-monitor.failures.index') }}" class="text-decoration-none small">
                        View all
                    </a>
                </div>
                <div class="card-body small p-0">
                    <ul class="list-group list-group-flush mb-0">
                        @forelse($recentFailures as $failure)
                            @php
                                $isResolved = $failure->isResolved();
                                $isNew = !$isResolved && $failure->failed_at && $failure->failed_at->gt(now()->subMinutes(30));
                                $statusLabel = $isResolved ? 'Resolved' : ($isNew ? 'New' : 'Requiring attention');
                                $badgeClass = $isResolved ? 'bg-success-subtle text-success' : ($isNew ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-dark');
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="me-3 flex-grow-1">
                                    <a href="{{ route('queue-monitor.failures.show', $failure) }}" class="fw-semibold text-decoration-none">
                                        {{ $failure->job_name }}
                                    </a>
                                    <div class="text-muted">
                                        {{ \Illuminate\Support\Str::limit($failure->exception_message, 80) }}
                                    </div>
                                    <div class="text-muted">
                                        {{ $failure->queue ?? 'default' }} • {{ $failure->environment }} •
                                        {{ optional($failure->failed_at)->diffForHumans() }}
                                    </div>
                                </div>
                                <span class="badge {{ $badgeClass }} align-self-center">
                                    {{ $statusLabel }}
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No recent failures.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
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

        // Auto refresh the dashboard if enabled
        const autoRefreshSeconds = {{ (int) $autoRefreshSeconds }};
        if (autoRefreshSeconds > 0) {
            setInterval(function() {
                window.location.reload();
            }, autoRefreshSeconds * 1000);
        }
    </script>
@endsection
