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
            flex: 1;
            min-width: 280px;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 14px;
            transition: all var(--transition-fast);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .filter-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 14px;
            color: var(--text-secondary);
            transition: all var(--transition-fast);
        }

        .filter-checkbox:hover {
            border-color: var(--border-color-light);
            color: var(--text-primary);
        }

        .filter-checkbox.active {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: white;
        }

        .filter-checkbox input {
            display: none;
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

        .pagination {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
        }

        .pagination li a,
        .pagination li span {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            transition: all var(--transition-fast);
        }

        .pagination li a:hover {
            background: var(--bg-tertiary);
            border-color: var(--border-color-light);
            color: var(--text-primary);
        }

        .pagination li.active span {
            background: var(--accent-gradient);
            border-color: transparent;
            color: white;
        }

        .pagination li.disabled span {
            opacity: 0.5;
            cursor: not-allowed;
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
            <form method="post" action="{{ route('queue-monitor.failures.clear') }}"
                onsubmit="return confirm('⚠️ This will permanently delete ALL queue monitor records.\n\nThis action cannot be undone. Are you sure?');">
                @csrf
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-danger">
                    <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                    Clear All
                </button>
            </form>
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="filters-bar">
        <form method="get" class="search-box" id="searchForm">
            <i data-lucide="search" style="width: 18px; height: 18px;"></i>
            <input type="text" name="search" placeholder="Search by job name..." value="{{ request('search') }}"
                onkeydown="if(event.key==='Enter'){this.form.submit()}">
            @if(request('unresolved'))
                <input type="hidden" name="unresolved" value="1">
            @endif
        </form>

        <div class="filter-group">
            <label class="filter-checkbox {{ request('unresolved') ? 'active' : '' }}" onclick="toggleFilter(this)">
                <input type="checkbox" {{ request('unresolved') ? 'checked' : '' }}>
                <i data-lucide="alert-circle" style="width: 16px; height: 16px;"></i>
                Unresolved Only
            </label>
            <button type="button" class="btn btn-secondary btn-sm" onclick="applyFilters()">
                <i data-lucide="filter" style="width: 14px; height: 14px;"></i>
                Apply Filters
            </button>
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    <div class="bulk-actions" id="bulkActions">
        <span class="bulk-count"><span id="selectedCount">0</span> selected</span>
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
                                <input type="checkbox" name="ids[]" value="{{ $failure->id }}"
                                    class="row-checkbox failure-checkbox" onclick="updateBulkActions()">
                            </td>
                            <td>
                                <a href="{{ route('queue-monitor.failures.show', $failure) }}" class="job-link">
                                    <div class="job-icon">
                                        <i data-lucide="zap" style="width: 16px; height: 16px;"></i>
                                    </div>
                                    <div class="job-info">
                                        <div class="job-name">{{ $failure->job_name }}</div>
                                        <div class="job-meta">
                                            {{ $failure->connection ?? 'default' }}
                                            @if($failure->retry_count > 0)
                                                <span class="retry-badge">
                                                    <i data-lucide="refresh-cw" style="width: 10px; height: 10px;"></i>
                                                    {{ $failure->retry_count }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <span class="queue-badge">{{ $failure->queue ?? 'default' }}</span>
                            </td>
                            <td>
                                <div class="exception-preview" title="{{ $failure->exception_message }}">
                                    {{ \Illuminate\Support\Str::limit($failure->exception_message, 60) }}
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
                                        @if(request('unresolved') || request('search'))
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

    {{-- Pagination --}}
    @if($failures->hasPages())
        <div class="pagination-wrapper">
            <div class="pagination-info">
                Showing {{ $failures->firstItem() }} to {{ $failures->lastItem() }} of {{ $failures->total() }} results
            </div>
            <nav>
                {{ $failures->withQueryString()->links() }}
            </nav>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        // Toggle filter checkbox
        function toggleFilter(label) {
            label.classList.toggle('active');
            const checkbox = label.querySelector('input');
            checkbox.checked = !checkbox.checked;
        }

        // Apply filters
        function applyFilters() {
            const searchInput = document.querySelector('.search-box input[name="search"]');
            const unresolvedCheckbox = document.querySelector('.filter-checkbox input');

            let url = new URL(window.location.href);
            url.searchParams.delete('search');
            url.searchParams.delete('unresolved');
            url.searchParams.delete('page');

            if (searchInput.value) {
                url.searchParams.set('search', searchInput.value);
            }
            if (unresolvedCheckbox.checked) {
                url.searchParams.set('unresolved', '1');
            }

            window.location.href = url.toString();
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

        // Clear selection
        function clearSelection() {
            document.querySelectorAll('.failure-checkbox').forEach(cb => {
                cb.checked = false;
                cb.closest('tr').classList.remove('selected');
            });
            document.getElementById('selectAll').checked = false;
            updateBulkActions();
        }

        // Re-initialize icons
        lucide.createIcons();
    </script>
@endsection