@extends('queue-monitor::layouts.app')

@section('breadcrumb', 'Failed Jobs')

@section('content')
    <style>
        /* Failures Page Specific Styles */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .page-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* Search and Filters */
        .filters-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            min-width: 180px;
            max-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 8px 12px 8px 36px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 13px;
            transition: all var(--transition-fast);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .search-box input::placeholder {
            color: var(--text-muted);
        }

        .search-box i,
        .search-box svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .filter-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            transition: all 0.2s ease;
            white-space: nowrap;
            user-select: none;
        }

        .filter-checkbox:hover {
            border-color: var(--accent-primary);
            color: var(--text-primary);
            background: var(--bg-tertiary);
        }

        .filter-checkbox.active {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .filter-checkbox.active:hover {
            background: var(--accent-secondary);
            border-color: var(--accent-secondary);
        }

        .filter-checkbox input {
            display: none;
        }
        
        .filter-checkbox i,
        .filter-checkbox svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }

        /* Bulk Actions Bar */
        .bulk-actions {
            display: none;
            align-items: center;
            gap: 16px;
            padding: 16px 24px;
            background: var(--accent-primary);
            border-radius: var(--radius-lg);
            margin-bottom: 16px;
            animation: slideDown 0.3s ease;
        }

        .bulk-actions.show {
            display: flex;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bulk-count {
            font-weight: 600;
            color: white;
        }

        .bulk-actions .btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
        }

        .bulk-actions .btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Table Enhancements */
        .failures-table {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .failures-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .failures-table th {
            background: var(--bg-tertiary);
            padding: 16px 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .failures-table th:first-child {
            width: 44px;
            text-align: center;
        }

        .failures-table td {
            padding: 18px 20px;
            font-size: 14px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .failures-table tr:last-child td {
            border-bottom: none;
        }

        .failures-table tr {
            transition: background var(--transition-fast);
        }

        .failures-table tr:hover td {
            background: var(--bg-tertiary);
        }

        .failures-table tr.selected td {
            background: rgba(102, 126, 234, 0.1);
        }

        /* Checkbox Styling */
        .row-checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--accent-primary);
            cursor: pointer;
        }

        /* Job Name Link */
        .job-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color var(--transition-fast);
        }

        .job-link:hover {
            color: var(--accent-primary);
        }

        .job-icon {
            width: 36px;
            height: 36px;
            background: var(--accent-gradient);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .job-info {
            min-width: 0;
        }

        .job-name {
            font-weight: 500;
            margin-bottom: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .job-meta {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Exception Message */
        .exception-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--text-muted);
            font-size: 13px;
        }

        /* Queue Badge */
        .queue-badge {
            display: inline-flex;
            padding: 4px 10px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
        }

        /* Timestamp */
        .timestamp {
            color: var(--text-muted);
            font-size: 13px;
            white-space: nowrap;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.resolved {
            background: var(--success-bg);
            color: var(--success);
        }

        .status-badge.unresolved {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        /* Retry Count Badge */
        .retry-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background: var(--warning-bg);
            color: var(--warning);
            border-radius: var(--radius-sm);
            font-size: 11px;
            font-weight: 600;
        }

        /* Pagination */
        .pagination-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .pagination-info {
            font-size: 14px;
            color: var(--text-muted);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            color: var(--text-muted);
            opacity: 0.4;
        }

        .empty-state-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .empty-state-text {
            font-size: 15px;
            color: var(--text-muted);
            max-width: 400px;
            margin: 0 auto;
        }

        /* Responsive Adjustments */
        @media (max-width: 1024px) {
            .exception-preview {
                max-width: 200px;
            }
        }

        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                max-width: none;
            }

            .failures-table {
                overflow-x: auto;
            }

            .exception-preview {
                display: none;
            }
        }

        /* Advanced Filters Styles */
        .advanced-filters {
            padding: 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: 16px;
        }

        .filters-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .filter-select,
        .filter-input {
            padding: 8px 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 13px;
        }

        .filter-select {
            min-width: 130px;
        }

        .filter-select:focus,
        .filter-input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .filter-input[type="date"] {
            width: 130px;
        }

        .filter-divider {
            width: 1px;
            height: 24px;
            background: var(--border-color);
            margin: 0 4px;
        }

        .export-group {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }

        .recurring-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            background: var(--warning-bg);
            color: var(--warning);
            border-radius: var(--radius-sm);
            font-size: 10px;
            font-weight: 600;
        }

        @media (max-width: 1200px) {
            .filter-divider {
                display: none;
            }
            
            .export-group {
                margin-left: 0;
                width: 100%;
                margin-top: 10px;
            }
        }

        @media (max-width: 768px) {
            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-select,
            .filter-input {
                width: 100%;
            }
            
            .search-box {
                max-width: none;
            }
            
            .filter-checkbox {
                justify-content: center;
            }
        }
    </style>

    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-header-left">
            <div>
                <h1 class="page-title">Failed Jobs</h1>
                <p class="page-subtitle">Manage and resolve queue failures</p>
            </div>
        </div>
        <div class="page-header-right">
            <a href="{{ route('queue-monitor.dashboard') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                Dashboard
            </a>
            <form method="post" action="{{ route('queue-monitor.failures.clear') }}" onsubmit="return confirm('⚠️ This will permanently delete ALL queue monitor records.\n\nThis action cannot be undone. Are you sure?');">
                @csrf
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-danger">
                    <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                    Clear All
                </button>
            </form>
        </div>
    </div>

    {{-- Advanced Filters --}}
    <div class="advanced-filters" id="advancedFilters">
        <div class="filters-row">
            {{-- Search Box --}}
            <div class="search-box">
                <i data-lucide="search" style="width: 16px; height: 16px;"></i>
                <input type="text" name="search" placeholder="Search job name..." value="{{ request('search') }}">
            </div>

            {{-- Quick Filter Checkboxes --}}
            <label class="filter-checkbox {{ request('unresolved') ? 'active' : '' }}" onclick="toggleFilter(this)">
                <input type="checkbox" name="unresolved" {{ request('unresolved') ? 'checked' : '' }}>
                <i data-lucide="alert-circle" style="width: 14px; height: 14px;"></i>
                <span>Unresolved</span>
            </label>

            <label class="filter-checkbox {{ request('recurring') ? 'active' : '' }}" onclick="toggleRecurring(this)">
                <input type="checkbox" name="recurring" {{ request('recurring') ? 'checked' : '' }}>
                <i data-lucide="repeat" style="width: 14px; height: 14px;"></i>
                <span>Recurring</span>
            </label>
            
            <div class="filter-divider"></div>

            <select name="queue" id="queueFilter" class="filter-select">
                <option value="">All Queues</option>
                @foreach ($filterOptions['queues'] ?? [] as $queue)
                    <option value="{{ $queue }}" {{ request('queue') == $queue ? 'selected' : '' }}>{{ $queue }}</option>
                @endforeach
            </select>

            <select name="connection" id="connectionFilter" class="filter-select">
                <option value="">All Connections</option>
                @foreach ($filterOptions['connections'] ?? [] as $connection)
                    <option value="{{ $connection }}" {{ request('connection') == $connection ? 'selected' : '' }}>{{ $connection }}</option>
                @endforeach
            </select>

            <select name="environment" id="environmentFilter" class="filter-select">
                <option value="">All Environments</option>
                @foreach ($filterOptions['environments'] ?? [] as $env)
                    <option value="{{ $env }}" {{ request('environment') == $env ? 'selected' : '' }}>{{ $env }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" id="dateFrom" class="filter-input" value="{{ request('date_from') }}" title="From Date">
            <input type="date" name="date_to" id="dateTo" class="filter-input" value="{{ request('date_to') }}" title="To Date">

            <button type="button" class="btn btn-primary btn-sm" onclick="applyFilters()">
                <i data-lucide="filter" style="width: 14px; height: 14px;"></i>
                Apply
            </button>

            <button type="button" class="btn btn-ghost btn-sm" onclick="clearFilters()">
                <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                Clear
            </button>
            
            <div class="export-group">
                <a href="{{ route('queue-monitor.failures.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="btn btn-ghost btn-sm" title="Export as CSV">
                    <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                    CSV
                </a>
                <a href="{{ route('queue-monitor.failures.export', array_merge(request()->query(), ['format' => 'json'])) }}" class="btn btn-ghost btn-sm" title="Export as JSON">
                    <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                    JSON
                </a>
            </div>
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    <div class="bulk-actions" id="bulkActions">
        <span class="bulk-count"><span id="selectedCount">0</span> selected</span>
        <button type="button" class="btn btn-sm" onclick="submitBulkRetry()">
            <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
            Bulk Retry
        </button>
        <button type="button" class="btn btn-sm" onclick="submitBulkResolve()">
            <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i>
            Mark as Resolved
        </button>
        <button type="button" class="btn btn-sm" onclick="clearSelection()">
            <i data-lucide="x" style="width: 14px; height: 14px;"></i>
            Clear Selection
        </button>
    </div>

    {{-- Failures Table --}}
    <form method="post" action="{{ route('queue-monitor.failures.bulk-resolve') }}" id="bulkForm">
        @csrf
        <div class="failures-table">
            <table>
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="row-checkbox" id="selectAll" onclick="toggleAllCheckboxes(this)">
                        </th>
                        <th>Job</th>
                        <th>Queue</th>
                        <th>Exception</th>
                        <th>Failed At</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($failures as $failure)
                        <tr data-id="{{ $failure->id }}">
                            <td style="text-align: center;">
                                <input type="checkbox" name="ids[]" value="{{ $failure->id }}" class="row-checkbox failure-checkbox" onclick="updateBulkActions()">
                            </td>
                            <td>
                                <a href="{{ route('queue-monitor.failures.show', $failure) }}" class="job-link">
                                    <div class="job-icon">
                                        <i data-lucide="zap" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <div class="job-info">
                                        <div class="job-name">{{ $failure->job_name }}</div>
                                        <div class="job-meta">
                                            @if ($failure->occurrences_count > 1)
                                                <span class="badge badge-info" style="font-size: 10px; padding: 2px 6px;">
                                                    {{ $failure->occurrences_count }} occurrences
                                                </span>
                                            @endif
                                            {{ $failure->connection ?? 'default' }}
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <span class="queue-badge">{{ $failure->queue ?? 'default' }}</span>
                            </td>
                            <td>
                                <div class="job-info" style="gap: 4px;">
                                    <div class="job-meta" style="color: var(--danger); font-weight: 600;">
                                        {{ class_basename($failure->exception_class) }}
                                    </div>
                                    <div class="exception-preview" title="{{ $failure->exception_message }}">
                                        {{ \Illuminate\Support\Str::limit($failure->exception_message, 60) }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="timestamp">
                                    {{ $failure->failed_at?->format('M d, Y') }}<br>
                                    <small>{{ $failure->failed_at?->format('H:i:s') }}</small>
                                </span>
                            </td>
                            <td>
                                @if ($failure->isResolved())
                                    <span class="status-badge resolved">Resolved</span>
                                @else
                                    <span class="status-badge unresolved">Unresolved</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i data-lucide="check-circle" class="empty-state-icon"></i>
                                    <div class="empty-state-title">No Failures Found</div>
                                    <div class="empty-state-text">
                                        @if (request('unresolved') || request('search'))
                                            No failures match your current filters. Try adjusting your search criteria.
                                        @else
                                            Your queues are running smoothly. No failed jobs have been recorded.
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Hidden form for bulk retry --}}
    <form method="post" action="{{ route('queue-monitor.failures.bulk-retry') }}" id="bulkRetryForm" style="display: none;">
        @csrf
        <div id="bulkRetryInputs"></div>
    </form>

    {{-- Pagination --}}
    @if ($failures->hasPages())
        <div class="pagination-wrapper">
            <div class="pagination-info">
                Showing {{ $failures->firstItem() }} to {{ $failures->lastItem() }} of {{ $failures->total() }} results
            </div>
            {{ $failures->withQueryString()->links('queue-monitor::components.pagination') }}
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        // Toggle filter checkbox
        function toggleFilter(label) {
            const checkbox = label.querySelector('input');
            checkbox.checked = !checkbox.checked;
            label.classList.toggle('active', checkbox.checked);
        }

        // Toggle recurring filter
        function toggleRecurring(label) {
            const checkbox = label.querySelector('input');
            checkbox.checked = !checkbox.checked;
            label.classList.toggle('active', checkbox.checked);
        }

        // Apply filters
        function applyFilters() {
            const searchInput = document.querySelector('.search-box input[name="search"]');
            const unresolvedCheckbox = document.querySelector('.filter-checkbox input[name="unresolved"]');
            const recurringCheckbox = document.querySelector('.filter-checkbox input[name="recurring"]');

            let url = new URL(window.location.href);
            // Clear existing params
            ['search', 'unresolved', 'recurring', 'queue', 'connection', 'environment', 'date_from', 'date_to', 'page'].forEach(p => {
                url.searchParams.delete(p);
            });

            if (searchInput?.value) {
                url.searchParams.set('search', searchInput.value);
            }

            if (unresolvedCheckbox?.checked) {
                url.searchParams.set('unresolved', '1');
            }

            if (recurringCheckbox?.checked) {
                url.searchParams.set('recurring', '1');
            }

            // Advanced filters
            const queue = document.getElementById('queueFilter')?.value;
            const connection = document.getElementById('connectionFilter')?.value;
            const environment = document.getElementById('environmentFilter')?.value;
            const dateFrom = document.getElementById('dateFrom')?.value;
            const dateTo = document.getElementById('dateTo')?.value;

            if (queue) url.searchParams.set('queue', queue);
            if (connection) url.searchParams.set('connection', connection);
            if (environment) url.searchParams.set('environment', environment);
            if (dateFrom) url.searchParams.set('date_from', dateFrom);
            if (dateTo) url.searchParams.set('date_to', dateTo);

            window.location.href = url.toString();
        }

        // Clear all filters
        function clearFilters() {
            const baseUrl = window.location.pathname;
            window.location.href = baseUrl;
        }

        // Toggle all checkboxes
        function toggleAllCheckboxes(checkbox) {
            document.querySelectorAll('.failure-checkbox').forEach(cb => {
                cb.checked = checkbox.checked;
                cb.closest('tr').classList.toggle('selected', checkbox.checked);
            });
            updateBulkActions();
        }

        // Update bulk actions bar
        function updateBulkActions() {
            const checked = document.querySelectorAll('.failure-checkbox:checked');
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');

            if (checked.length > 0) {
                bulkActions.classList.add('show');
                selectedCount.textContent = checked.length;
            } else {
                bulkActions.classList.remove('show');
            }

            // Update select all checkbox
            const allCheckboxes = document.querySelectorAll('.failure-checkbox');
            const selectAll = document.getElementById('selectAll');
            selectAll.checked = allCheckboxes.length > 0 && checked.length === allCheckboxes.length;

            // Update row selection state
            document.querySelectorAll('.failure-checkbox').forEach(cb => {
                cb.closest('tr').classList.toggle('selected', cb.checked);
            });
        }

        // Submit bulk resolve
        function submitBulkResolve() {
            const form = document.getElementById('bulkForm');
            form.submit();
        }

        // Submit bulk retry
        function submitBulkRetry() {
            const checked = document.querySelectorAll('.failure-checkbox:checked');
            if (checked.length === 0) return;

            const inputsContainer = document.getElementById('bulkRetryInputs');
            inputsContainer.innerHTML = '';

            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = cb.value;
                inputsContainer.appendChild(input);
            });

            document.getElementById('bulkRetryForm').submit();
        }

        // Clear selection
        function clearSelection() {
            document.querySelectorAll('.failure-checkbox').forEach(cb => {
                cb.checked = false;
                cb.closest('tr').classList.remove('selected');
            });
            document.getElementById('selectAll').checked = false;
            updateBulkActions();
        }

        // Initialize filter checkbox states on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.filter-checkbox').forEach(label => {
                const checkbox = label.querySelector('input');
                if (checkbox && checkbox.checked) {
                    label.classList.add('active');
                }
            });
        });

        // Re-initialize icons
        lucide.createIcons();
    </script>
@endsection
