<?php

namespace NikunjKothiya\QueueMonitor\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Bus;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;
use NikunjKothiya\QueueMonitor\Http\Requests\BulkResolveFailuresRequest;
use NikunjKothiya\QueueMonitor\Http\Requests\ClearFailuresRequest;
use NikunjKothiya\QueueMonitor\Http\Requests\ResolveFailureRequest;
use NikunjKothiya\QueueMonitor\Http\Requests\RetryWithPayloadRequest;
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

        // Apply filters
        if ($request->boolean('unresolved')) {
            $query->unresolved();
        }

        if ($request->filled('search')) {
            $query->where('job_name', 'like', '%' . $request->input('search') . '%');
        }

        // Advanced filters
        if ($request->filled('queue')) {
            $query->where('queue', $request->input('queue'));
        }

        if ($request->filled('connection')) {
            $query->where('connection', $request->input('connection'));
        }

        if ($request->filled('environment')) {
            $query->where('environment', $request->input('environment'));
        }

        if ($request->boolean('recurring')) {
            $query->recurring();
        }

        if ($request->filled('date_from')) {
            $query->where('failed_at', '>=', $request->input('date_from') . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('failed_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        if ($request->filled('exception_class')) {
            $query->where('exception_class', 'like', '%' . $request->input('exception_class') . '%');
        }

        $failures = $query->paginate(25);

        // Get filter options for dropdowns
        $filterOptions = $this->getFilterOptions();

        return view('queue-monitor::failures.index', [
            'failures' => $failures,
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Get unique values for filter dropdowns.
     */
    protected function getFilterOptions(): array
    {
        return [
            'queues' => QueueFailure::select('queue')->distinct()->whereNotNull('queue')->pluck('queue'),
            'connections' => QueueFailure::select('connection')->distinct()->whereNotNull('connection')->pluck('connection'),
            'environments' => QueueFailure::select('environment')->distinct()->whereNotNull('environment')->pluck('environment'),
        ];
    }

    /**
     * Export failures as CSV or JSON.
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'csv');
        $maxRecords = (int) config('queue-monitor.export.max_records', 10000);

        $query = QueueFailure::query()->orderBy('failed_at', 'desc');

        // Apply same filters as index
        if ($request->boolean('unresolved')) {
            $query->unresolved();
        }

        if ($request->filled('queue')) {
            $query->where('queue', $request->input('queue'));
        }

        if ($request->filled('connection')) {
            $query->where('connection', $request->input('connection'));
        }

        if ($request->filled('environment')) {
            $query->where('environment', $request->input('environment'));
        }

        if ($request->boolean('recurring')) {
            $query->recurring();
        }

        if ($request->filled('date_from')) {
            $query->where('failed_at', '>=', $request->input('date_from') . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('failed_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $failures = $query->limit($maxRecords)->get([
            'id', 'uuid', 'connection', 'queue', 'job_name', 'job_class',
            'exception_class', 'exception_message', 'file', 'line',
            'failed_at', 'resolved_at', 'resolution_notes', 'retry_count',
            'is_recurring', 'environment', 'hostname'
        ]);

        if ($format === 'json') {
            return response()->json([
                'exported_at' => now()->toIso8601String(),
                'count' => $failures->count(),
                'failures' => $failures,
            ], 200, [
                'Content-Disposition' => 'attachment; filename="queue-failures-' . now()->format('Y-m-d') . '.json"',
            ]);
        }

        // CSV export
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="queue-failures-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($failures) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'ID', 'UUID', 'Connection', 'Queue', 'Job Name', 'Job Class',
                'Exception Class', 'Exception Message', 'File', 'Line',
                'Failed At', 'Resolved At', 'Resolution Notes', 'Retry Count',
                'Is Recurring', 'Environment', 'Hostname'
            ]);

            foreach ($failures as $failure) {
                fputcsv($file, [
                    $failure->id,
                    $failure->uuid,
                    $failure->connection,
                    $failure->queue,
                    $failure->job_name,
                    $failure->job_class,
                    $failure->exception_class,
                    $failure->exception_message,
                    $failure->file,
                    $failure->line,
                    $failure->failed_at?->toIso8601String(),
                    $failure->resolved_at?->toIso8601String(),
                    $failure->resolution_notes,
                    $failure->retry_count,
                    $failure->is_recurring ? 'Yes' : 'No',
                    $failure->environment,
                    $failure->hostname,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show(QueueFailure $failure)
    {
        $failure->occurrences_count = QueueFailure::where('group_hash', $failure->group_hash)->count();

        // Get smart insights for this failure
        $insights = app(\NikunjKothiya\QueueMonitor\Services\SmartInsightsService::class)->analyzeFailure($failure);

        // Extract job properties for Smart Editor
        $jobProperties = $this->getJobProperties($failure->payload);
        $jobClass = $this->getJobClass($failure->payload);

        return view('queue-monitor::failures.show', [
            'failure' => $failure,
            'insights' => $insights,
            'jobProperties' => $jobProperties,
            'jobClass' => $jobClass,
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

    /**
     * Retry a job with modified payload or properties.
     */
    public function retryWithPayload(RetryWithPayloadRequest $request, QueueFailure $failure): RedirectResponse
    {
        $modifiedPayload = $request->input('payload');
        $retryNotes = $request->input('retry_notes');
        $jobProperties = $request->input('job_properties'); // JSON string of properties

        $job = null;
        $finalPayload = $modifiedPayload;

        // Strategy 1: Smart Property Update (Preferred)
        if ($jobProperties) {
            try {
                $properties = json_decode($jobProperties, true, 512, JSON_THROW_ON_ERROR);
                $job = $this->reconstructJobFromProperties($failure->payload, $properties);
                
                // If successful, we need to generate the new payload string for storage
                // Note: We can't easily generate the FULL payload string without serializing the job
                // and putting it back into the JSON structure.
                if ($job) {
                    $originalDecoded = json_decode($failure->payload, true);
                    $originalDecoded['data']['command'] = serialize($job);
                    $finalPayload = json_encode($originalDecoded);
                }
            } catch (\Throwable $e) {
                return back()->with('queue-monitor.error', 'Failed to reconstruct job from properties: ' . $e->getMessage());
            }
        } 
        // Strategy 2: Raw Payload Update (Fallback)
        else {
            // Validate and decode the modified payload
            $decodedPayload = json_decode($modifiedPayload, true, 512);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('queue-monitor.error', 'Invalid JSON payload: ' . json_last_error_msg());
            }

            // Reconstruct the job from the modified payload
            $job = $this->reconstructJobFromModifiedPayload($modifiedPayload);
        }

        if (! $job) {
            return back()->with('queue-monitor.error', 'Unable to reconstruct job from input.');
        }

        try {
            Bus::dispatch($job);
        } catch (\Throwable $e) {
            return back()->with('queue-monitor.error', 'Failed to dispatch job: ' . $e->getMessage());
        }

        // Track the retry with modified payload
        $failure->increment('retry_count');
        $failure->update([
            'last_retried_at' => now(),
            'modified_payload' => $finalPayload,
            'retry_notes' => $retryNotes,
            'retried_by' => $request->user()?->getKey(),
        ]);

        return back()->with('queue-monitor.success', 'Job has been re-dispatched with modified data. Retry count: ' . $failure->retry_count);
    }

    /**
     * Bulk retry multiple failed jobs.
     */
    public function bulkRetry(BulkResolveFailuresRequest $request): RedirectResponse
    {
        $ids = $request->validated('ids');
        
        $failures = QueueFailure::whereIn('id', $ids)->get();
        
        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($failures as $failure) {
            if (! $failure->payload) {
                $failCount++;
                $errors[] = "#{$failure->id}: Missing payload";
                continue;
            }

            $job = $this->reconstructJobFromPayload($failure->payload);
            
            if (! $job) {
                $failCount++;
                $errors[] = "#{$failure->id}: Unable to reconstruct job";
                continue;
            }

            try {
                Bus::dispatch($job);
                
                $failure->increment('retry_count');
                $failure->update([
                    'last_retried_at' => now(),
                    'retried_by' => $request->user()?->getKey(),
                ]);
                
                $successCount++;
            } catch (\Throwable $e) {
                $failCount++;
                $errors[] = "#{$failure->id}: " . $e->getMessage();
            }
        }

        $message = "Bulk retry completed: {$successCount} succeeded, {$failCount} failed.";
        
        if ($failCount > 0 && count($errors) <= 3) {
            $message .= ' Errors: ' . implode('; ', $errors);
        }

        if ($failCount === 0) {
            return back()->with('queue-monitor.success', $message);
        }
        
        return back()->with('queue-monitor.warning', $message);
    }

    /**
     * Reconstruct job from modified payload JSON string.
     */
    protected function reconstructJobFromModifiedPayload(string $payload): mixed
    {
        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            if (! isset($decoded['data']['command'])) {
                return null;
            }

            // Get the serialized command
            $serialized = $decoded['data']['command'];
            
            // Unserialize it to get the job instance
            return unserialize($serialized, ['allowed_classes' => true]);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Extract properties from a serialized job in the payload.
     */
    protected function getJobProperties(?string $payload): ?array
    {
        if (! $payload) return null;

        try {
            $job = $this->reconstructJobFromPayload($payload);
            if (! is_object($job)) return null;

            $reflection = new \ReflectionClass($job);
            $properties = [];

            foreach ($reflection->getProperties() as $property) {
                // Skip internal Laravel properties that shouldn't be edited manually
                if (in_array($property->getName(), ['job', 'connection', 'queue', 'chainConnection', 'chainQueue', 'delay', 'middleware', 'chained'])) {
                    continue;
                }

                $property->setAccessible(true);
                $value = $property->getValue($job);

                // We only want to expose scalar values or simple arrays for editing
                // Complex objects might be too hard to edit via simple UI
                if (is_scalar($value) || is_array($value) || is_null($value)) {
                    $properties[$property->getName()] = $value;
                }
            }

            return $properties;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function getJobClass(?string $payload): ?string
    {
        if (! $payload) return null;
        try {
            $job = $this->reconstructJobFromPayload($payload);
            return is_object($job) ? get_class($job) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function reconstructJobFromProperties(string $originalPayload, array $properties): mixed
    {
        $job = $this->reconstructJobFromPayload($originalPayload);
        
        if (! is_object($job)) {
            throw new \Exception('Could not reconstruct original job');
        }

        $reflection = new \ReflectionClass($job);

        foreach ($properties as $key => $value) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                $property->setValue($job, $value);
            }
        }

        return $job;
    }
}


