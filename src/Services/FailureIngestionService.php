<?php

namespace NikunjKothiya\QueueMonitor\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;

/**
 * High-performance failure ingestion with minimal database overhead.
 * 
 * Design Philosophy:
 * - Single INSERT per failure (no reads during ingestion)
 * - Defer expensive operations (recurring detection, stats)
 * - Use memory-efficient batch processing
 * - Auto-detect priority from job characteristics (no config needed)
 */
class FailureIngestionService
{
    /**
     * Keywords that indicate critical/high priority jobs.
     * These are detected automatically from queue names and job class names.
     */
    protected array $criticalKeywords = [
        // Financial/Transactional
        'payment', 'pay', 'billing', 'invoice', 'charge', 'refund', 
        'transaction', 'transfer', 'payout', 'subscription', 'order',
        'checkout', 'purchase', 'money', 'wallet', 'credit', 'debit',
        
        // Critical operations
        'critical', 'urgent', 'important', 'priority', 'high',
        
        // Auth/Security
        'auth', 'login', 'password', 'security', 'verify', 'otp', '2fa',
    ];

    protected array $highKeywords = [
        // Communication
        'email', 'mail', 'notification', 'notify', 'sms', 'message',
        'alert', 'webhook', 'callback', 'push',
        
        // Sync/Integration
        'sync', 'import', 'export', 'integration', 'api',
        
        // User-facing
        'user', 'customer', 'client', 'member',
    ];

    protected array $lowKeywords = [
        // Background/Batch
        'report', 'analytics', 'stats', 'log', 'cleanup', 'prune',
        'archive', 'backup', 'cache', 'index', 'reindex',
        'batch', 'bulk', 'queue', 'scheduled', 'cron',
        'low', 'background', 'async',
    ];

    /**
     * Ingest a failure with minimal overhead.
     * This is the HOT PATH - every microsecond counts.
     */
    public function ingest(array $data): QueueFailure
    {
        // Calculate group hash with improved accuracy
        $groupHash = $this->calculateGroupHash($data);
        
        // Calculate priority score automatically from job characteristics
        $priorityScore = $this->calculatePriorityScore($data);
        
        // Single INSERT - no SELECT queries
        $failure = QueueFailure::create([
            'connection' => $data['connection'],
            'queue' => $data['queue'],
            'job_name' => $data['job_name'],
            'job_class' => $data['job_class'],
            'payload' => $data['payload'],
            'exception_class' => $data['exception_class'],
            'exception_message' => $data['exception_message'],
            'exception_context' => $data['exception_context'] ?? null,
            'file' => $data['file'],
            'line' => $data['line'],
            'stack_trace' => $data['stack_trace'],
            'group_hash' => $groupHash,
            'hostname' => $data['hostname'],
            'environment' => $data['environment'],
            'failed_at' => $data['failed_at'] ?? now(),
            'priority_score' => $priorityScore,
            'is_recurring' => false,
        ]);
        
        // Increment counter in cache (O(1) operation)
        $this->incrementFailureCounter($failure);
        
        return $failure;
    }
    
    /**
     * Improved group hash with better accuracy.
     * Includes normalized exception message to avoid false grouping.
     */
    protected function calculateGroupHash(array $data): string
    {
        // Normalize exception message - remove variable parts
        $normalizedMessage = $this->normalizeExceptionMessage(
            $data['exception_message'] ?? ''
        );
        
        $hashInput = implode('|', [
            $data['job_class'] ?? '',
            $data['exception_class'] ?? '',
            $data['file'] ?? '',
            $data['line'] ?? 0,
            $normalizedMessage,
        ]);
        
        // Use xxh3 if available (PHP 8.1+), fallback to md5
        if (in_array('xxh3', hash_algos())) {
            return hash('xxh3', $hashInput);
        }
        
        return md5($hashInput);
    }
    
    /**
     * Normalize exception message by removing variable parts.
     * This improves grouping accuracy significantly.
     */
    protected function normalizeExceptionMessage(string $message): string
    {
        // Remove UUIDs
        $message = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', '{uuid}', $message);
        
        // Remove numeric IDs (standalone numbers)
        $message = preg_replace('/\b\d{4,}\b/', '{id}', $message);
        
        // Remove email addresses
        $message = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '{email}', $message);
        
        // Remove timestamps
        $message = preg_replace('/\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/', '{timestamp}', $message);
        
        // Remove IP addresses
        $message = preg_replace('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', '{ip}', $message);
        
        // Remove file paths with line numbers
        $message = preg_replace('/\/[\w\/.-]+:\d+/', '{path}', $message);
        
        // Truncate to first 200 chars for hash consistency
        return substr(trim($message), 0, 200);
    }
    
    /**
     * Calculate priority score automatically based on job characteristics.
     * 
     * This works WITHOUT any configuration - it analyzes:
     * 1. Queue name keywords
     * 2. Job class name keywords  
     * 3. Exception severity
     * 4. Environment
     * 
     * Score: 0-100 (higher = more critical)
     */
    protected function calculatePriorityScore(array $data): int
    {
        $score = 50; // Base score (medium priority)
        
        $queue = strtolower($data['queue'] ?? 'default');
        $jobClass = strtolower($data['job_class'] ?? $data['job_name'] ?? '');
        $jobName = strtolower($data['job_name'] ?? '');
        $exceptionClass = $data['exception_class'] ?? '';
        $exceptionMessage = strtolower($data['exception_message'] ?? '');
        
        // Combine all text for keyword matching
        $searchText = "{$queue} {$jobClass} {$jobName}";
        
        // ============================================
        // 1. KEYWORD-BASED PRIORITY (from queue + job name)
        // ============================================
        
        $foundCritical = false;
        $foundHigh = false;
        $foundLow = false;
        
        // Check for critical keywords (+45 points)
        foreach ($this->criticalKeywords as $keyword) {
            if (str_contains($searchText, $keyword)) {
                $score += 45;
                $foundCritical = true;
                break; // Only add once
            }
        }
        
        // Check for high priority keywords (+25 points, only if not already critical)
        if (!$foundCritical) {
            foreach ($this->highKeywords as $keyword) {
                if (str_contains($searchText, $keyword)) {
                    $score += 25;
                    $foundHigh = true;
                    break;
                }
            }
        }
        
        // Check for low priority keywords (-20 points, only if no other keywords found)
        if (!$foundCritical && !$foundHigh) {
            foreach ($this->lowKeywords as $keyword) {
                if (str_contains($searchText, $keyword)) {
                    $score -= 20;
                    $foundLow = true;
                    break;
                }
            }
        }
        
        // ============================================
        // 2. EXCEPTION SEVERITY
        // ============================================
        
        // Critical exceptions (+10 points)
        $criticalExceptions = [
            'PDOException', 'QueryException', 'ConnectionException',
            'AuthenticationException', 'AuthorizationException',
            'TokenMismatchException', 'DecryptException',
        ];
        foreach ($criticalExceptions as $exc) {
            if (str_contains($exceptionClass, $exc)) {
                $score += 10;
                break;
            }
        }
        
        // Database/Infrastructure errors in message (+10 points)
        $criticalPatterns = [
            'sqlstate', 'connection refused', 'connection reset',
            'too many connections', 'deadlock', 'lock wait timeout',
            'out of memory', 'memory exhausted', 'disk full',
        ];
        foreach ($criticalPatterns as $pattern) {
            if (str_contains($exceptionMessage, $pattern)) {
                $score += 10;
                break;
            }
        }
        
        // External service errors (+5 points)
        $externalPatterns = [
            'curl error', 'timeout', 'ssl certificate', 'dns',
            '500 internal', '502 bad gateway', '503 service',
        ];
        foreach ($externalPatterns as $pattern) {
            if (str_contains($exceptionMessage, $pattern)) {
                $score += 5;
                break;
            }
        }
        
        // ============================================
        // 3. ENVIRONMENT BOOST
        // ============================================
        
        // Production environment (+5 points)
        $env = strtolower($data['environment'] ?? '');
        if ($env === 'production' || $env === 'prod') {
            $score += 5;
        }
        
        // ============================================
        // 4. USER-CONFIGURED OVERRIDES (optional)
        // ============================================
        
        // Check if user has configured specific queue priorities
        $configuredCritical = config('queue-monitor.priority.critical_queues', []);
        $configuredHigh = config('queue-monitor.priority.high_queues', []);
        
        if (!empty($configuredCritical)) {
            foreach ($configuredCritical as $q) {
                if (str_contains($queue, strtolower($q))) {
                    $score = max($score, 90); // Ensure at least 90
                    break;
                }
            }
        }
        
        if (!empty($configuredHigh)) {
            foreach ($configuredHigh as $q) {
                if (str_contains($queue, strtolower($q))) {
                    $score = max($score, 70); // Ensure at least 70
                    break;
                }
            }
        }
        
        // Clamp to 0-100 range
        return max(0, min(100, $score));
    }
    
    /**
     * Increment failure counter in cache for fast recurring detection.
     */
    protected function incrementFailureCounter(QueueFailure $failure): void
    {
        $key = "qm:count:{$failure->group_hash}";
        $windowMinutes = config('queue-monitor.analytics.recurring_window_hours', 24) * 60;
        
        // Atomic increment with TTL
        Cache::increment($key);
        
        // Set expiry if this is a new key
        if (Cache::get($key) == 1) {
            Cache::put($key, 1, now()->addMinutes($windowMinutes));
        }
    }
    
    /**
     * Check if a group hash is recurring (fast cache lookup).
     */
    public function isRecurring(string $groupHash): bool
    {
        $threshold = config('queue-monitor.analytics.recurring_threshold', 3);
        $count = (int) Cache::get("qm:count:{$groupHash}", 0);
        
        return $count >= $threshold;
    }
    
    /**
     * Get current failure count for a group (from cache).
     */
    public function getGroupCount(string $groupHash): int
    {
        return (int) Cache::get("qm:count:{$groupHash}", 0);
    }
}
