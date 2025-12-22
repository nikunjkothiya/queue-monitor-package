@extends('queue-monitor::layouts.app')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-800">
            Failure #{{ $failure->id }} – {{ $failure->job_name }}
        </h1>
        <a href="{{ route('queue-monitor.failures.index') }}"
           class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
            Back
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white shadow rounded-lg p-4">
            <div class="text-xs text-gray-500 uppercase">Queue</div>
            <div class="mt-2 text-sm text-gray-800">{{ $failure->queue ?? '-' }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
            <div class="text-xs text-gray-500 uppercase">Connection</div>
            <div class="mt-2 text-sm text-gray-800">{{ $failure->connection ?? '-' }}</div>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
            <div class="text-xs text-gray-500 uppercase">Status</div>
            <div class="mt-2 text-sm">
                @if($failure->isResolved())
                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                        Resolved
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">
                        Unresolved
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-sm font-semibold text-gray-700 mb-2">Exception Message</h2>
                <pre class="text-sm text-red-700 whitespace-pre-wrap">{{ $failure->exception_message }}</pre>
            </div>

            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-sm font-semibold text-gray-700 mb-2">Stack Trace</h2>
                <pre class="text-xs text-gray-700 whitespace-pre-wrap overflow-x-auto">{{ $failure->stack_trace }}</pre>
            </div>

            @if($failure->payload)
                <div class="bg-white shadow rounded-lg p-4">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2">Payload</h2>
                    <pre class="text-xs text-gray-700 whitespace-pre-wrap overflow-x-auto">{{ $failure->payload }}</pre>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="bg-white shadow rounded-lg p-4">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">Actions</h2>
                <form method="post" action="{{ route('queue-monitor.failures.retry', $failure) }}" class="mb-2">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Retry Job
                    </button>
                </form>

                @if(! $failure->isResolved())
                    <form method="post" action="{{ route('queue-monitor.failures.resolve', $failure) }}" class="space-y-2">
                        @csrf
                        <textarea name="resolution_notes" rows="3"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                                  placeholder="Resolution notes (optional)"></textarea>
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md text-xs font-medium text-white bg-green-600 hover:bg-green-700">
                            Mark as Resolved
                        </button>
                    </form>
                @else
                    <div class="text-xs text-gray-600">
                        <div>Resolved at: {{ $failure->resolved_at?->format('Y-m-d H:i:s') }}</div>
                        @if($failure->resolver)
                            <div>By: {{ $failure->resolver->name ?? ('#'.$failure->resolver->getKey()) }}</div>
                        @endif
                        @if($failure->resolution_notes)
                            <div class="mt-2">
                                <div class="font-semibold">Notes:</div>
                                <div class="whitespace-pre-wrap">{{ $failure->resolution_notes }}</div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="bg-white shadow rounded-lg p-4 text-xs text-gray-600 space-y-1">
                <div><span class="font-semibold">ID:</span> {{ $failure->id }}</div>
                <div><span class="font-semibold">UUID:</span> {{ $failure->uuid ?? '-' }}</div>
                <div><span class="font-semibold">Environment:</span> {{ $failure->environment }}</div>
                <div><span class="font-semibold">Failed At:</span> {{ $failure->failed_at?->format('Y-m-d H:i:s') }}</div>
                <div><span class="font-semibold">Created At:</span> {{ $failure->created_at?->format('Y-m-d H:i:s') }}</div>
                <div><span class="font-semibold">Updated At:</span> {{ $failure->updated_at?->format('Y-m-d H:i:s') }}</div>
            </div>
        </div>
    </div>
@endsection


