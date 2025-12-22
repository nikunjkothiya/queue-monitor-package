<?php

namespace NikunjKothiya\QueueMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Bus;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;
use NikunjKothiya\QueueMonitor\Http\Requests\BulkResolveFailuresRequest;
use NikunjKothiya\QueueMonitor\Http\Requests\ClearFailuresRequest;
use NikunjKothiya\QueueMonitor\Http\Requests\ResolveFailureRequest;
use Illuminate\Http\Request;

class FailureController extends Controller
{
    public function index(Request $request)
    {
        $statsSubquery = QueueFailure::query()
            ->select('group_hash')
            ->selectRaw('COUNT(*) as occurrences_count')
            ->selectRaw('MAX(id) as latest_id')
            ->groupBy('group_hash');

        $query = QueueFailure::query()
            ->joinSub($statsSubquery, 'stats', function ($join) {
                $join->on('queue_failures.id', '=', 'stats.latest_id');
            })
            ->orderBy('failed_at', 'desc');

        if ($request->boolean('unresolved')) {
            $query->unresolved();
        }

        if ($request->filled('search')) {
            $query->where('job_name', 'like', '%' . $request->input('search') . '%');
        }

        $failures = $query->paginate(25);

        return view('queue-monitor::failures.index', [
            'failures' => $failures,
        ]);
    }

    public function show(QueueFailure $failure)
    {
        $failure->occurrences_count = QueueFailure::where('group_hash', $failure->group_hash)->count();

        return view('queue-monitor::failures.show', [
            'failure' => $failure,
        ]);
    }

    public function retry(QueueFailure $failure): RedirectResponse
    {
        if (! $failure->payload) {
            return back()->with('queue-monitor.error', 'Cannot retry: missing payload.');
        }

        $job = $this->reconstructJobFromPayload($failure->payload);

        if (! $job) {
            return back()->with('queue-monitor.error', 'Unable to reconstruct job from payload.');
        }

        Bus::dispatch($job);

        // Track retry
        $failure->increment('retry_count');
        $failure->update(['last_retried_at' => now()]);

        return back()->with('queue-monitor.success', 'Job has been re-dispatched. Retry count: ' . $failure->retry_count);
    }

    public function resolve(ResolveFailureRequest $request, QueueFailure $failure): RedirectResponse
    {
        QueueFailure::where('group_hash', $failure->group_hash)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'resolution_notes' => $request->input('resolution_notes'),
                'resolved_by' => $request->user()?->getKey(),
            ]);

        return back()->with('queue-monitor.success', 'Issue and related failures marked as resolved.');
    }

    public function bulkResolve(BulkResolveFailuresRequest $request): RedirectResponse
    {
        $ids = $request->validated('ids');

        $hashes = QueueFailure::whereIn('id', $ids)->pluck('group_hash')->filter()->unique();

        QueueFailure::whereIn('group_hash', $hashes)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'resolved_by' => $request->user()?->getKey(),
            ]);

        return back()->with('queue-monitor.success', 'Selected issues and related failures marked as resolved.');
    }

    public function clearAll(ClearFailuresRequest $request): RedirectResponse
    {
        QueueFailure::query()->delete();

        return back()->with('queue-monitor.success', 'All queue monitor records have been deleted.');
    }

    protected function reconstructJobFromPayload(string $payload): mixed
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($decoded['data']['command'])) {
                return null;
            }

            return unserialize($decoded['data']['command'], ['allowed_classes' => true]);
        } catch (\Throwable) {
            return null;
        }
    }
}


