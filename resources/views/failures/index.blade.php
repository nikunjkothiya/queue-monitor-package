@extends('queue-monitor::layouts.app')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between mb-3">
        <h1 class="h5 mb-2 mb-md-0">Queue Failures</h1>
        <div class="d-flex align-items-center gap-2">
            <form method="get" class="d-flex align-items-center gap-2">
                <div class="form-check small mb-0">
                    <input type="checkbox" class="form-check-input" id="unresolved" name="unresolved" value="1" @checked(request('unresolved'))>
                    <label class="form-check-label" for="unresolved">Only unresolved</label>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    Filter
                </button>
            </form>

            <form method="post" action="{{ route('queue-monitor.failures.clear') }}" onsubmit="return confirm('This will permanently delete all queue monitor records. Are you sure?');">
                @csrf
                <input type="hidden" name="confirm" value="yes">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    Clear all records
                </button>
            </form>
        </div>
    </div>

    <form method="post" action="{{ route('queue-monitor.failures.bulk-resolve') }}">
        @csrf
        <div class="table-responsive bg-white shadow-sm rounded">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 30px;">
                            <input type="checkbox" onclick="document.querySelectorAll('.failure-checkbox').forEach(c => c.checked = this.checked)">
                        </th>
                        <th>Job</th>
                        <th>Queue</th>
                        <th>Message</th>
                        <th>Failed At</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($failures as $failure)
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]" value="{{ $failure->id }}" class="failure-checkbox">
                            </td>
                            <td>
                                <a href="{{ route('queue-monitor.failures.show', $failure) }}">
                                    {{ $failure->job_name }}
                                </a>
                            </td>
                            <td class="text-muted">{{ $failure->queue ?? '-' }}</td>
                            <td class="text-muted text-truncate" title="{{ $failure->exception_message }}">
                                {{ \Illuminate\Support\Str::limit($failure->exception_message, 80) }}
                            </td>
                            <td class="text-muted">
                                {{ $failure->failed_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="text-end">
                                @if ($failure->isResolved())
                                    <span class="badge bg-success-subtle text-success">Resolved</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Unresolved</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No failures found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-3">
            <button type="submit" class="btn btn-sm btn-success">
                Mark selected as resolved
            </button>
            <div>
                {{ $failures->withQueryString()->links() }}
            </div>
        </div>
    </form>
@endsection
