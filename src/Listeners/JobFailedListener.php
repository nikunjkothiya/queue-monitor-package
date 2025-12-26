<?php

namespace NikunjKothiya\QueueMonitor\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use NikunjKothiya\QueueMonitor\Services\AlertDispatchService;
use NikunjKothiya\QueueMonitor\Services\FailureIngestionService;

/**
 * Listens for job failures and processes them with minimal overhead.
 * 
 * Design: This is the HOT PATH - every operation here adds latency
 * to the queue worker. We optimize for:
 * 1. Single database INSERT (no reads)
 * 2. Cache-based recurring detection (no queries)
 * 3. Async alert dispatch where possible
 */
class JobFailedListener
{
    public function __construct(
        protected FailureIngestionService $ingestion,
        protected AlertDispatchService $alertDispatch
    ) {
    }

    public function handle(JobFailed $event): void
    {
        try {
            $this->processFailure($event);
        } catch (\Throwable $e) {
            // Never let monitoring crash the queue worker
            Log::error('Queue Monitor: Failed to process job failure', [
                'error' => $e->getMessage(),
                'job' => $event->job?->getName(),
            ]);
        }
    }
    
    protected function processFailure(JobFailed $event): void
    {
        $payload = $event->job?->getRawBody();
        $jobName = $this->resolveJobName($event);
        $exception = $event->exception;

        // Extract exception context if available (Laravel 8+)
        $exceptionContext = null;
        if (method_exists($exception, 'context')) {
            try {
                /** @var callable $contextMethod */
                $contextMethod = [$exception, 'context'];
                $context = $contextMethod();
                if (is_array($context) && !empty($context)) {
                    $exceptionContext = $context;
                }
            } catch (\Throwable) {
                // Ignore context extraction failures
            }
        }

        // Prepare data for ingestion
        $data = [
            'connection' => $event->connectionName,
            'queue' => $event->job?->getQueue(),
            'job_name' => $jobName,
            'job_class' => $jobName,
            'payload' => $payload,
            'exception_class' => $exception::class,
            'exception_message' => $this->truncateMessage($exception->getMessage()),
            'exception_context' => $exceptionContext,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack_trace' => $this->truncateStackTrace($exception->getTraceAsString()),
            'hostname' => gethostname(),
            'environment' => app()->environment(),
            'failed_at' => now(),
        ];

        // Single INSERT with priority calculation
        $failure = $this->ingestion->ingest($data);

        // Check if this is now a recurring failure (cache lookup, O(1))
        if ($this->ingestion->isRecurring($failure->group_hash)) {
            // Update only if not already marked (avoids unnecessary UPDATE)
            if (!$failure->is_recurring) {
                $failure->update(['is_recurring' => true]);
            }
        }

        // Process alerts (with smart throttling)
        $this->alertDispatch->processFailure($failure);
    }

    protected function resolveJobName(JobFailed $event): string
    {
        if (method_exists($event->job, 'resolveName')) {
            return $event->job->resolveName();
        }

        return $event->job?->getName() ?? 'Unknown Job';
    }
    
    /**
     * Truncate exception message to prevent oversized records.
     */
    protected function truncateMessage(string $message): string
    {
        $maxLength = 10000;
        
        if (strlen($message) <= $maxLength) {
            return $message;
        }
        
        return substr($message, 0, $maxLength) . '... [truncated]';
    }
    
    /**
     * Truncate stack trace to reasonable size.
     */
    protected function truncateStackTrace(string $trace): string
    {
        $maxLength = 65000; // Leave room for TEXT column
        
        if (strlen($trace) <= $maxLength) {
            return $trace;
        }
        
        return substr($trace, 0, $maxLength) . "\n... [truncated]";
    }
}


