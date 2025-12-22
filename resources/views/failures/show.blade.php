@extends('queue-monitor::layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 mb-0">
            Failure #{{ $failure->id }} – {{ $failure->job_name }}
        </h1>
        <a href="{{ route('queue-monitor.failures.index') }}" class="btn btn-sm btn-outline-secondary">
            Back
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body small">
                    <div class="text-muted text-uppercase mb-1">Queue</div>
                    <div>{{ $failure->queue ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body small">
                    <div class="text-muted text-uppercase mb-1">Connection</div>
                    <div>{{ $failure->connection ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body small">
                    <div class="text-muted text-uppercase mb-1">Status</div>
                    <div>
                        @if ($failure->isResolved())
                            <span class="badge bg-success-subtle text-success">Resolved</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger">Unresolved</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header py-2">
                    <strong>Exception Message</strong>
                </div>
                <div class="card-body">
                    <pre class="small text-danger mb-0">{{ $failure->exception_message }}</pre>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header py-2">
                    <strong>Stack Trace</strong>
                </div>
                <div class="card-body">
                    <pre class="small mb-0 text-muted overflow-auto">{{ $failure->stack_trace }}</pre>
                </div>
            </div>

            @if ($failure->payload)
                <div class="card mb-3">
                    <div class="card-header py-2">
                        <strong>Payload</strong>
                    </div>
                    <div class="card-body">
                        <pre class="small mb-0 text-muted overflow-auto">{{ $failure->payload }}</pre>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header py-2">
                    <strong>Actions</strong>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('queue-monitor.failures.retry', $failure) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            Retry Job
                        </button>
                    </form>

                    @if (!$failure->isResolved())
                        <form method="post" action="{{ route('queue-monitor.failures.resolve', $failure) }}">
                            @csrf
                            <div class="mb-2">
                                <textarea name="resolution_notes" rows="3" class="form-control form-control-sm" placeholder="Resolution notes (optional)"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                Mark as Resolved
                            </button>
                        </form>
                    @else
                        <div class="small text-muted">
                            <div>Resolved at: {{ $failure->resolved_at?->format('Y-m-d H:i:s') }}</div>
                            @if ($failure->resolver)
                                <div>By: {{ $failure->resolver->name ?? '#' . $failure->resolver->getKey() }}</div>
                            @endif
                            @if ($failure->resolution_notes)
                                <div class="mt-2">
                                    <div class="fw-semibold">Notes:</div>
                                    <div class="text-wrap">{{ $failure->resolution_notes }}</div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="card small">
                <div class="card-header py-2">
                    <strong>Meta</strong>
                </div>
                <div class="card-body">
                    <div><span class="fw-semibold">ID:</span> {{ $failure->id }}</div>
                    <div><span class="fw-semibold">UUID:</span> {{ $failure->uuid ?? '-' }}</div>
                    <div><span class="fw-semibold">Environment:</span> {{ $failure->environment }}</div>
                    <div><span class="fw-semibold">Failed At:</span> {{ $failure->failed_at?->format('Y-m-d H:i:s') }}</div>
                    <div><span class="fw-semibold">Created At:</span> {{ $failure->created_at?->format('Y-m-d H:i:s') }}</div>
                    <div><span class="fw-semibold">Updated At:</span> {{ $failure->updated_at?->format('Y-m-d H:i:s') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
