@extends('queue-monitor::layouts.app')

@section('breadcrumb', 'Dashboard')

@section('content')
    <style>
        /* Dashboard Specific Styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all var(--transition-normal);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-gradient);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg), var(--shadow-glow);
            border-color: var(--border-color-light);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card.danger::before {
            background: var(--danger);
        }

        .stat-card.success::before {
            background: var(--success);
        }

        .stat-card.warning::before {
            background: var(--warning);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon.primary {
            background: rgba(102, 126, 234, 0.15);
            color: var(--accent-primary);
        }

        .stat-icon.danger {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .stat-icon.success {
            background: var(--success-bg);
            color: var(--success);
        }

        .stat-icon.warning {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .stat-sublabel {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Health Score Ring */
        .health-score-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
        }

        .health-score-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .health-score-content {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .health-ring {
            position: relative;
            width: 120px;
            height: 120px;
        }

        .health-ring svg {
            transform: rotate(-90deg);
            width: 120px;
            height: 120px;
        }

        .health-ring-bg {
            fill: none;
            stroke: var(--border-color);
            stroke-width: 8;
        }

        .health-ring-progress {
            fill: none;
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease;
        }

        .health-ring-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .health-ring-value {
            font-size: 28px;
            font-weight: 700;
        }

        .health-ring-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .health-details {
            flex: 1;
        }

        .health-status {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .health-description {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        @media (max-width: 1024px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        .chart-container {
            height: 300px;
            position: relative;
        }

        /* Driver Cards */
        .drivers-section {
            margin-bottom: 32px;
        }

        .drivers-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 16px;
        }

        .drivers-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 768px) {
            .drivers-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .driver-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px 20px;
            text-align: center;
            transition: all var(--transition-fast);
        }

        .driver-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-2px);
        }

        .driver-card.active {
            border-color: var(--success);
            background: var(--success-bg);
        }

        .driver-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .driver-subtitle {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Bottom Grid */
        .bottom-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        @media (max-width: 1200px) {
            .bottom-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .bottom-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Top Jobs List */
        .job-list {
            list-style: none;
        }

        .job-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .job-item:last-child {
            border-bottom: none;
        }

        .job-name {
            font-size: 14px;
            font-weight: 500;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-right: 16px;
        }

        .job-count {
            font-size: 13px;
            font-weight: 600;
            color: var(--danger);
            background: var(--danger-bg);
            padding: 4px 12px;
            border-radius: 100px;
        }

        /* Recent Failures List */
        .failure-list {
            list-style: none;
        }

        .failure-item {
            display: flex;
            gap: 12px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .failure-item:last-child {
            border-bottom: none;
        }

        .failure-status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-top: 6px;
            flex-shrink: 0;
        }

        .failure-status-dot.new {
            background: var(--danger);
            animation: pulse 2s ease-in-out infinite;
        }

        .failure-status-dot.attention {
            background: var(--warning);
        }

        .failure-status-dot.resolved {
            background: var(--success);
        }

        .failure-content {
            flex: 1;
            min-width: 0;
        }

        .failure-job-name {
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .failure-job-name a {
            color: var(--text-primary);
            text-decoration: none;
            transition: color var(--transition-fast);
        }

        .failure-job-name a:hover {
            color: var(--accent-primary);
        }

        .failure-message {
            font-size: 13px;
            color: var(--text-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-bottom: 4px;
        }

        .failure-meta {
            font-size: 12px;
            color: var(--text-muted);
        }

        .failure-meta span {
            margin-right: 8px;
        }

        /* Alert Config Card */
        .alert-config-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .alert-config-item:last-child {
            margin-bottom: 0;
        }

        .alert-config-label {
            color: var(--text-muted);
        }

        .alert-config-value {
            font-weight: 600;
            margin-left: auto;
        }

        /* Diagnostics */
        .diagnostics-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .diagnostics-item:last-child {
            border-bottom: none;
        }

        .diagnostics-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 4px;
        }

        .diagnostics-value {
            font-weight: 500;
        }

        .diagnostics-messages {
            margin-top: 16px;
            padding: 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-sm);
        }

        .diagnostics-messages li {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 6px;
            list-style-type: disc;
            margin-left: 16px;
        }

        .diagnostics-messages li:last-child {
            margin-bottom: 0;
        }
    </style>

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Monitor your queue health and performance</p>
        </div>
        <a href="{{ route('queue-monitor.failures.index') }}" class="btn btn-primary">
            <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
            View All Failures
        </a>
    </div>

    {{-- Stats Grid --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Failures</span>
                <div class="stat-icon primary">
                    <i data-lucide="activity" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="stat-value">{{ number_format($totalFailures) }}</div>
            <div class="stat-sublabel">Last {{ $daysWindow }} days</div>
        </div>

        <div class="stat-card danger">
            <div class="stat-header">
                <span class="stat-label">Unresolved</span>
                <div class="stat-icon danger">
                    <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="stat-value text-danger">{{ number_format($unresolvedCount) }}</div>
            <div class="stat-sublabel">Requiring attention</div>
        </div>

        <div class="stat-card success">
            <div class="stat-header">
                <span class="stat-label">Resolution Rate</span>
                <div class="stat-icon success">
                    <i data-lucide="check-circle" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            <div class="stat-value text-success">{{ number_format($resolutionRate, 1) }}%</div>
            <div class="stat-sublabel">Resolved in window</div>
        </div>

        <div class="stat-card warning">
            <div class="stat-header">
                <span class="stat-label">Avg Resolution Time</span>
                <div class="stat-icon warning">
                    <i data-lucide="clock" style="width: 20px; height: 20px;"></i>
                </div>
            </div>
            @php
                $avgInterval = $avgResolutionSeconds !== null
                    ? \Carbon\CarbonInterval::seconds($avgResolutionSeconds)->cascade()
                    : null;
            @endphp
            <div class="stat-value">
                @if ($avgInterval)
                    {{ $avgInterval->hours }}h {{ $avgInterval->minutes }}m
                @else
                    —
                @endif
            </div>
            <div class="stat-sublabel">From failure to resolution</div>
        </div>
    </div>

    {{-- Queue Drivers --}}
    <div class="drivers-section">
        <div class="drivers-title">Supported Queue Drivers</div>
        <div class="drivers-grid">
            @foreach ($queueDrivers as $driver)
                <div
                    class="driver-card {{ strtolower($queueDiagnostics['driver'] ?? '') === strtolower($driver['name']) ? 'active' : '' }}">
                    <div class="driver-name">{{ $driver['name'] }}</div>
                    <div class="driver-subtitle">{{ $driver['subtitle'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="charts-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Failures Over Time</div>
                    <div class="card-subtitle">Last {{ $daysWindow }} days</div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="failuresOverTimeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="health-score-card">
            <div class="health-score-header">
                <div class="card-title">Queue Health Score</div>
            </div>
            @php
                $healthLabel = $healthScore >= 80 ? 'Healthy' : ($healthScore >= 50 ? 'Warning' : 'Critical');
                $healthColor = $healthScore >= 80 ? 'var(--success)' : ($healthScore >= 50 ? 'var(--warning)' : 'var(--danger)');
                $circumference = 2 * 3.14159 * 45;
                $dashOffset = $circumference - ($healthScore / 100) * $circumference;
            @endphp
            <div class="health-score-content">
                <div class="health-ring">
                    <svg viewBox="0 0 100 100">
                        <circle class="health-ring-bg" cx="50" cy="50" r="45"></circle>
                        <circle class="health-ring-progress" cx="50" cy="50" r="45"
                            style="stroke: {{ $healthColor }}; stroke-dasharray: {{ $circumference }}; stroke-dashoffset: {{ $dashOffset }};">
                        </circle>
                    </svg>
                    <div class="health-ring-text">
                        <div class="health-ring-value" style="color: {{ $healthColor }}">{{ $healthScore }}</div>
                        <div class="health-ring-label">Score</div>
                    </div>
                </div>
                <div class="health-details">
                    <div class="health-status" style="color: {{ $healthColor }}">{{ $healthLabel }}</div>
                    <div class="health-description">
                        Higher scores indicate fewer unresolved failures and faster resolution times.
                        Score is computed based on resolution rate, backlog, and recent failure trends.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Grid --}}
    <div class="bottom-grid">
        {{-- Top Failing Jobs --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Top Failing Jobs</div>
            </div>
            <div class="card-body" style="padding: 16px 24px;">
                @if($topFailingJobs->isNotEmpty())
                    <ul class="job-list">
                        @foreach($topFailingJobs as $job)
                            <li class="job-item">
                                <span class="job-name" title="{{ $job->job_name }}">{{ $job->job_name }}</span>
                                <span class="job-count">{{ $job->count }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="empty-state">
                        <i data-lucide="check-circle" class="empty-state-icon"></i>
                        <div class="empty-state-title">All Clear!</div>
                        <div class="empty-state-text">No failing jobs recorded yet</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Failures --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Recent Failures</div>
                <a href="{{ route('queue-monitor.failures.index') }}" class="btn btn-sm btn-ghost">
                    View all
                    <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                </a>
            </div>
            <div class="card-body" style="padding: 16px 24px;">
                @if($recentFailures->isNotEmpty())
                    <ul class="failure-list">
                        @foreach($recentFailures->take(5) as $failure)
                            @php
                                $isResolved = $failure->isResolved();
                                $isNew = !$isResolved && $failure->failed_at && $failure->failed_at->gt(now()->subMinutes(30));
                                $statusClass = $isResolved ? 'resolved' : ($isNew ? 'new' : 'attention');
                            @endphp
                            <li class="failure-item">
                                <div class="failure-status-dot {{ $statusClass }}"></div>
                                <div class="failure-content">
                                    <div class="failure-job-name">
                                        <a href="{{ route('queue-monitor.failures.show', $failure) }}">
                                            {{ $failure->job_name }}
                                        </a>
                                    </div>
                                    <div class="failure-message">
                                        {{ \Illuminate\Support\Str::limit($failure->exception_message, 60) }}
                                    </div>
                                    <div class="failure-meta">
                                        <span>{{ $failure->queue ?? 'default' }}</span>
                                        <span>•</span>
                                        <span>{{ optional($failure->failed_at)->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="empty-state">
                        <i data-lucide="inbox" class="empty-state-icon"></i>
                        <div class="empty-state-title">No Recent Failures</div>
                        <div class="empty-state-text">Your queues are running smoothly</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Alert Config & Diagnostics --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Configuration</div>
            </div>
            <div class="card-body" style="padding: 16px 24px;">
                <div class="text-sm font-semibold mb-3" style="color: var(--text-muted);">Smart Alert Throttling</div>

                <div class="alert-config-item">
                    <i data-lucide="timer" style="width: 16px; height: 16px; color: var(--text-muted);"></i>
                    <span class="alert-config-label">Cooldown</span>
                    <span class="alert-config-value">{{ $alertConfig['throttle_minutes'] ?? 5 }} min</span>
                </div>

                <div class="alert-config-item">
                    <i data-lucide="clock" style="width: 16px; height: 16px; color: var(--text-muted);"></i>
                    <span class="alert-config-label">Window</span>
                    <span class="alert-config-value">{{ $alertConfig['window_minutes'] ?? 5 }} min</span>
                </div>

                <div class="alert-config-item">
                    <i data-lucide="alert-triangle" style="width: 16px; height: 16px; color: var(--text-muted);"></i>
                    <span class="alert-config-label">Min Failures</span>
                    <span class="alert-config-value">{{ $alertConfig['min_failures_for_alert'] ?? 1 }}</span>
                </div>

                <div class="mt-4">
                    <div class="text-sm font-semibold mb-3" style="color: var(--text-muted);">Queue Diagnostics</div>

                    <div class="diagnostics-item">
                        <div class="diagnostics-label">Connection</div>
                        <div class="diagnostics-value">{{ $queueDiagnostics['default'] ?? 'not set' }}</div>
                    </div>

                    <div class="diagnostics-item">
                        <div class="diagnostics-label">Driver</div>
                        <div class="diagnostics-value">{{ $queueDiagnostics['driver'] ?? 'unknown' }}</div>
                    </div>

                    @if(!empty($queueDiagnostics['messages']))
                        <div class="diagnostics-messages">
                            <ul>
                                @foreach ($queueDiagnostics['messages'] as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Failures Over Time Chart
        const failuresOverTimeLabels = @json($failuresOverTime->pluck('date'));
        const failuresOverTimeData = @json($failuresOverTime->pluck('count'));

        if (failuresOverTimeLabels.length) {
            const ctx = document.getElementById('failuresOverTimeChart').getContext('2d');

            // Get computed styles for theming
            const computedStyle = getComputedStyle(document.documentElement);
            const textMuted = computedStyle.getPropertyValue('--text-muted').trim();
            const borderColor = computedStyle.getPropertyValue('--border-color').trim();

            // Create gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(239, 68, 68, 0.3)');
            gradient.addColorStop(1, 'rgba(239, 68, 68, 0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: failuresOverTimeLabels,
                    datasets: [{
                        label: 'Failures',
                        data: failuresOverTimeData,
                        borderColor: '#ef4444',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#ef4444',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 15, 35, 0.95)',
                            titleColor: '#ffffff',
                            bodyColor: '#a0a0b8',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: textMuted,
                                maxTicksLimit: 7
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: borderColor
                            },
                            ticks: {
                                color: textMuted,
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Re-initialize icons after page load
        lucide.createIcons();
    </script>
@endsection