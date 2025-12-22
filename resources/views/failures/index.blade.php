@extends('queue-monitor::layouts.app')

@section('content')
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-800">Queue Failures</h1>
        <div class="flex items-center gap-3">
            <form method="get" class="flex items-center space-x-2">
                <label class="inline-flex items-center text-sm text-gray-600">
                    <input type="checkbox" name="unresolved" value="1"
                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                           @checked(request('unresolved'))>
                    <span class="ml-2">Only unresolved</span>
                </label>
                <button type="submit"
                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Filter
                </button>
            </form>

            <form method="post" action="{{ route('queue-monitor.failures.clear') }}"
                  onsubmit="return confirm('This will permanently delete all queue monitor records. Are you sure?');">
                @csrf
                <input type="hidden" name="confirm" value="yes">
                <button type="submit"
                        class="inline-flex items-center px-3 py-1.5 border border-red-200 rounded-md text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100">
                    Clear all records
                </button>
            </form>
        </div>
    </div>

    <form method="post" action="{{ route('queue-monitor.failures.bulk-resolve') }}">
        @csrf
        <div class="overflow-x-auto bg-white shadow rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2">
                        <input type="checkbox" onclick="document.querySelectorAll('.failure-checkbox').forEach(c => c.checked = this.checked)">
                    </th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500">Job</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500">Queue</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500">Message</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500">Failed At</th>
                    <th class="px-3 py-2 text-right font-medium text-gray-500">Status</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($failures as $failure)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2">
                            <input type="checkbox" name="ids[]" value="{{ $failure->id }}" class="failure-checkbox">
                        </td>
                        <td class="px-3 py-2">
                            <a href="{{ route('queue-monitor.failures.show', $failure) }}" class="text-indigo-600 hover:underline">
                                {{ $failure->job_name }}
                            </a>
                        </td>
                        <td class="px-3 py-2 text-gray-500">{{ $failure->queue ?? '-' }}</td>
                        <td class="px-3 py-2 text-gray-600 truncate" title="{{ $failure->exception_message }}">
                            {{ \Illuminate\Support\Str::limit($failure->exception_message, 80) }}
                        </td>
                        <td class="px-3 py-2 text-gray-500">
                            {{ $failure->failed_at?->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            @if($failure->isResolved())
                                <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                    Resolved
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">
                                    Unresolved
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-gray-500">No failures found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex items-center justify-between">
            <button type="submit"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-xs font-medium text-white bg-green-600 hover:bg-green-700">
                Mark selected as resolved
            </button>
            <div>
                {{ $failures->withQueryString()->links() }}
            </div>
        </div>
    </form>
@endsection


