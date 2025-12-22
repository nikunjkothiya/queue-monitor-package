@extends('queue-monitor::layouts.app')

@section('breadcrumb')
    <a href="{{ route('queue-monitor.failures.index') }}">Failed Jobs</a>
    <span class="breadcrumb-separator">/</span>
    <span>#{{ $failure->id }}</span>
@endsection

@section('content')
    <style>
        /* Failure Detail Page Styles */
        .detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .detail-header-left {
            flex: 1;
            min-width: 300px;
        }

        .detail-title {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }

        .detail-icon {
            width: 56px;
            height: 56px;
            background: var(--accent-gradient);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
            box-shadow: var(--shadow-glow);
        }

        .detail-info h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .detail-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 14px;
            color: var(--text-muted);
            flex-wrap: wrap;
        }

        .detail-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .detail-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* Stats Row */
        .detail-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        @media (max-width: 1024px) {
            .detail-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .detail-stats {
                grid-template-columns: 1fr;
            }
        }

        .detail-stat {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 20px;
        }

        .detail-stat-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .detail-stat-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Content Grid */
        .detail-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        @media (max-width: 1024px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Code Blocks */
        .code-section {
            margin-bottom: 24px;
        }

        .code-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-bottom: none;
            border-radius: var(--radius-md) var(--radius-md) 0 0;
        }

        .code-title {
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .copy-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .copy-btn:hover {
            background: var(--bg-card-hover);
            color: var(--text-primary);
            border-color: var(--border-color-light);
        }

        .copy-btn.copied {
            background: var(--success-bg);
            color: var(--success);
            border-color: var(--success);
        }

        .code-content {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 0 0 var(--radius-md) var(--radius-md);
            overflow: hidden;
        }

        .code-content pre {
            margin: 0;
            padding: 20px;
            font-size: 13px;
            line-height: 1.6;
            overflow-x: auto;
            max-height: 400px;
            background: transparent;
            border: none;
            border-radius: 0;
        }

        .code-content.exception pre {
            color: var(--danger);
        }

        .code-content.stack pre {
            color: var(--text-muted);
            font-size: 12px;
        }

        .code-content.payload pre {
            color: var(--text-secondary);
        }

        /* Collapsible Section */
        .collapsible {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .collapsible-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: var(--bg-tertiary);
            cursor: pointer;
            transition: background var(--transition-fast);
        }

        .collapsible-header:hover {
            background: var(--bg-card-hover);
        }

        .collapsible-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 14px;
        }

        .collapsible-icon {
            transition: transform var(--transition-normal);
        }

        .collapsible.open .collapsible-icon {
            transform: rotate(180deg);
        }

        .collapsible-content {
            display: none;
            padding: 20px;
            background: var(--bg-secondary);
        }

        .collapsible.open .collapsible-content {
            display: block;
        }

        /* Actions Card */
        .actions-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .actions-header {
            padding: 20px 24px;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
        }

        .actions-body {
            padding: 24px;
        }

        .action-form {
            margin-bottom: 20px;
        }

        .action-form:last-child {
            margin-bottom: 0;
        }

        .action-form label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .action-form textarea {
            width: 100%;
            margin-bottom: 12px;
        }

        /* Resolution Info */
        .resolution-info {
            background: var(--success-bg);
            border-radius: var(--radius-md);
            padding: 20px;
        }

        .resolution-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--success);
            margin-bottom: 12px;
        }

        .resolution-detail {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(16, 185, 129, 0.2);
            font-size: 14px;
        }

        .resolution-detail:last-child {
            border-bottom: none;
        }

        .resolution-detail-label {
            color: var(--text-muted);
        }

        .resolution-detail-value {
            color: var(--text-primary);
            font-weight: 500;
        }

        .resolution-notes {
            margin-top: 16px;
            padding: 16px;
            background: var(--bg-secondary);
            border-radius: var(--radius-sm);
        }

        .resolution-notes-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .resolution-notes-text {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Meta Card */
        .meta-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .meta-header {
            padding: 20px 24px;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
        }

        .meta-body {
            padding: 8px 0;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 24px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        .meta-item:last-child {
            border-bottom: none;
        }

        .meta-label {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-value {
            color: var(--text-primary);
            font-weight: 500;
            text-align: right;
            word-break: break-all;
        }

        /* Timeline */
        .timeline {
            margin-top: 24px;
        }

        .timeline-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 16px;
            color: var(--text-muted);
        }

        .timeline-item {
            display: flex;
            gap: 16px;
            padding-bottom: 20px;
            position: relative;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 24px;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }

        .timeline-item:last-child::before {
            display: none;
        }

        .timeline-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            z-index: 1;
        }

        .timeline-dot.created {
            background: var(--info-bg);
            color: var(--info);
        }

        .timeline-dot.failed {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .timeline-dot.retried {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .timeline-dot.resolved {
            background: var(--success-bg);
            color: var(--success);
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-label {
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .timeline-time {
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>

    {{-- Detail Header --}}
    <div class="detail-header">
        <div class="detail-header-left">
            <div class="detail-title">
                <div class="detail-icon">
                    <i data-lucide="zap" style="width: 24px; height: 24px;"></i>
                </div>
                <div class="detail-info">
                    <h1>{{ $failure->job_name }}</h1>
                    <div class="detail-meta">
                        <div class="detail-meta-item">
                            <i data-lucide="hash" style="width: 14px; height: 14px;"></i>
                            Failure #{{ $failure->id }}
                        </div>
                        <div class="detail-meta-item">
                            @if ($failure->isResolved())
                                <span class="badge badge-success">
                                    <i data-lucide="check" style="width: 12px; height: 12px;"></i>
                                    Resolved
                                </span>
                            @else
                                <span class="badge badge-danger">
                                    <i data-lucide="alert-circle" style="width: 12px; height: 12px;"></i>
                                    Unresolved
                                </span>
                            @endif
                        </div>
                        @if($failure->retry_count > 0)
                            <div class="detail-meta-item">
                                <span class="badge badge-warning">
                                    <i data-lucide="refresh-cw" style="width: 12px; height: 12px;"></i>
                                    Retried {{ $failure->retry_count }}x
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="detail-actions">
            <a href="{{ route('queue-monitor.failures.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                Back to List
            </a>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="detail-stats">
        <div class="detail-stat">
            <div class="detail-stat-label">Occurrences</div>
            <div class="detail-stat-value">
                <span class="badge badge-info">{{ $failure->occurrences_count }}</span>
            </div>
        </div>
        <div class="detail-stat">
            <div class="detail-stat-label">Queue / Connection</div>
            <div class="detail-stat-value" style="font-size: 14px;">
                {{ $failure->queue ?? 'default' }} ({{ $failure->connection ?? 'default' }})
            </div>
        </div>
        <div class="detail-stat">
            <div class="detail-stat-label">Environment / Host</div>
            <div class="detail-stat-value" style="font-size: 14px;">
                {{ $failure->environment }} @ {{ $failure->hostname ?? 'unknown' }}
            </div>
        </div>
        <div class="detail-stat">
            <div class="detail-stat-label">Failed At</div>
            <div class="detail-stat-value">{{ $failure->failed_at?->format('M d, Y H:i') }}</div>
        </div>
    </div>

    {{-- Content Grid --}}
    <div class="detail-grid">
        {{-- Left Column - Exception & Stack Trace --}}
        <div>
            {{-- Exception Message --}}
            <div class="code-section">
                <div class="code-header">
                    <div class="code-title">
                        <i data-lucide="alert-circle" style="width: 16px; height: 16px; color: var(--danger);"></i>
                        Exception Message
                    </div>
                    <button class="copy-btn" onclick="copyToClipboard(this, 'exceptionText')">
                        <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                        Copy
                    </button>
                </div>
                <div class="code-content exception">
                    <pre id="exceptionText">{{ $failure->exception_message }}</pre>
                </div>
            </div>

            {{-- Stack Trace --}}
            <div class="collapsible open">
                <div class="collapsible-header" onclick="toggleCollapsible(this)">
                    <div class="collapsible-title">
                        <i data-lucide="layers" style="width: 16px; height: 16px;"></i>
                        Stack Trace
                    </div>
                    <i data-lucide="chevron-down" class="collapsible-icon" style="width: 18px; height: 18px;"></i>
                </div>
                <div class="collapsible-content">
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
                        <button class="copy-btn" onclick="copyToClipboard(this, 'stackTraceText')">
                            <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                            Copy Stack Trace
                        </button>
                    </div>
                    <div class="code-content stack" style="border-radius: var(--radius-md);">
                        <pre id="stackTraceText">{{ $failure->stack_trace }}</pre>
                    </div>
                </div>
            </div>

            {{-- Payload --}}
            @if ($failure->payload)
                <div class="collapsible">
                    <div class="collapsible-header" onclick="toggleCollapsible(this)">
                        <div class="collapsible-title">
                            <i data-lucide="package" style="width: 16px; height: 16px;"></i>
                            Job Payload
                        </div>
                        <i data-lucide="chevron-down" class="collapsible-icon" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div class="collapsible-content">
                        <div style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
                            <button class="copy-btn" onclick="copyToClipboard(this, 'payloadText')">
                                <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
                                Copy Payload
                            </button>
                        </div>
                        <div class="code-content payload" style="border-radius: var(--radius-md);">
                            <pre id="payloadText">{{ json_encode(json_decode($failure->payload), JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column - Actions & Meta --}}
        <div>
            {{-- Actions Card --}}
            <div class="actions-card">
                <div class="actions-header">
                    <i data-lucide="settings" style="width: 16px; height: 16px; margin-right: 8px;"></i>
                    Actions
                </div>
                <div class="actions-body">
                    {{-- Retry Job --}}
                    <form method="post" action="{{ route('queue-monitor.failures.retry', $failure) }}" class="action-form">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i data-lucide="refresh-cw" style="width: 16px; height: 16px;"></i>
                            Retry Job
                        </button>
                    </form>

                    @if (!$failure->isResolved())
                        {{-- Resolve --}}
                        <form method="post" action="{{ route('queue-monitor.failures.resolve', $failure) }}"
                            class="action-form">
                            @csrf
                            <label for="resolution_notes">Resolution Notes (optional)</label>
                            <textarea name="resolution_notes" id="resolution_notes" rows="3" class="form-input"
                                placeholder="Describe how the issue was resolved..."></textarea>
                            <button type="submit" class="btn btn-success" style="width: 100%;">
                                <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
                                Mark as Resolved
                            </button>
                        </form>
                    @else
                        {{-- Resolution Info --}}
                        <div class="resolution-info">
                            <div class="resolution-header">
                                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                                Resolved
                            </div>
                            <div class="resolution-detail">
                                <span class="resolution-detail-label">Resolved At</span>
                                <span class="resolution-detail-value">
                                    {{ $failure->resolved_at?->format('M d, Y H:i:s') }}
                                </span>
                            </div>
                            @if ($failure->resolver)
                                <div class="resolution-detail">
                                    <span class="resolution-detail-label">Resolved By</span>
                                    <span class="resolution-detail-value">
                                        {{ $failure->resolver->name ?? '#' . $failure->resolver->getKey() }}
                                    </span>
                                </div>
                            @endif
                            @if ($failure->resolution_notes)
                                <div class="resolution-notes">
                                    <div class="resolution-notes-label">Notes</div>
                                    <div class="resolution-notes-text">{{ $failure->resolution_notes }}</div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Meta Card --}}
            <div class="meta-card">
                <div class="meta-header">
                    <i data-lucide="info" style="width: 16px; height: 16px; margin-right: 8px;"></i>
                    Metadata
                </div>
                <div class="meta-body">
                    <div class="meta-item">
                        <span class="meta-label">
                            <i data-lucide="hash" style="width: 14px; height: 14px;"></i>
                            ID
                        </span>
                        <span class="meta-value">{{ $failure->id }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">
                            <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i>
                            Exception
                        </span>
                        <span class="meta-value" style="color: var(--danger);">{{ $failure->exception_class }}</span>
                    </div>
                    <div class="meta-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                        <span class="meta-label">
                            <i data-lucide="file-text" style="width: 14px; height: 14px;"></i>
                            Failure Location
                        </span>
                        <span class="meta-value" style="text-align: left; font-size: 13px; color: var(--text-muted);">
                            {{ $failure->file }}:<strong>{{ $failure->line }}</strong>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">
                            <i data-lucide="fingerprint" style="width: 14px; height: 14px;"></i>
                            Fingerprint
                        </span>
                        <span class="meta-value" style="font-size: 11px; font-family: monospace; color: var(--text-muted);">
                            {{ $failure->group_hash }}
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">
                            <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                            First Seen
                        </span>
                        <span class="meta-value">{{ $failure->created_at?->format('M d, Y H:i:s') }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">
                            <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                            Last Seen
                        </span>
                        <span class="meta-value">{{ $failure->failed_at?->format('M d, Y H:i:s') }}</span>
                    </div>
                    @if($failure->retry_count > 0)
                        <div class="meta-item">
                            <span class="meta-label">
                                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                                Last Retried
                            </span>
                            <span class="meta-value">{{ $failure->last_retried_at?->format('M d, Y H:i:s') ?? '-' }}</span>
                        </div>
                    @endif
                </div>

                {{-- Timeline --}}
                <div style="padding: 24px;">
                    <div class="timeline-title">Timeline</div>
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot failed">
                                <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-label">Job Failed</div>
                                <div class="timeline-time">{{ $failure->failed_at?->format('M d, Y H:i:s') }}</div>
                            </div>
                        </div>
                        @if($failure->retry_count > 0)
                            <div class="timeline-item">
                                <div class="timeline-dot retried">
                                    <i data-lucide="refresh-cw" style="width: 12px; height: 12px;"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-label">Retried {{ $failure->retry_count }}x</div>
                                    <div class="timeline-time">
                                        {{ $failure->last_retried_at?->format('M d, Y H:i:s') ?? 'Unknown' }}
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if($failure->isResolved())
                            <div class="timeline-item">
                                <div class="timeline-dot resolved">
                                    <i data-lucide="check" style="width: 12px; height: 12px;"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-label">Resolved</div>
                                    <div class="timeline-time">{{ $failure->resolved_at?->format('M d, Y H:i:s') }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Toggle collapsible sections
        function toggleCollapsible(header) {
            header.closest('.collapsible').classList.toggle('open');
            lucide.createIcons();
        }

        // Copy to clipboard
        function copyToClipboard(button, elementId) {
            const text = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(text).then(() => {
                button.classList.add('copied');
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i data-lucide="check" style="width: 14px; height: 14px;"></i> Copied!';
                lucide.createIcons();

                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = originalHTML;
                    lucide.createIcons();
                }, 2000);
            });
        }

        // Re-initialize icons
        lucide.createIcons();
    </script>
@endsection