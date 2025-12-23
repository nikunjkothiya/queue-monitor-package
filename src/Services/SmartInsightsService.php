<?php

namespace NikunjKothiya\QueueMonitor\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;

/**
 * SmartInsightsService - Provides intelligent analysis and suggestions
 * to help developers quickly identify and fix queue issues.
 */
class SmartInsightsService
{
    /**
     * Common exception patterns and their likely causes with solutions.
     */
    protected array $exceptionPatterns = [
        // 1. Code/Logic Errors
        'ParseError' => [
            'category' => 'Syntax Error',
            'icon' => 'code',
            'suggestions' => [
                'Check your job class for PHP syntax errors',
                'Verify all variables are properly initialized',
                'Ensure proper type hints and data types are being passed',
            ],
        ],
        'TypeError' => [
            'category' => 'Type Error',
            'icon' => 'code',
            'suggestions' => [
                'Ensure proper type hints and data types are being passed',
                'Check for null values passed to typed arguments',
            ],
        ],
        'BadMethodCallException' => [
            'category' => 'Method Not Found',
            'icon' => 'code',
            'suggestions' => [
                'Check if called methods exist on objects',
                'Verify method visibility (public/protected)',
            ],
        ],
        'Class .* not found' => [
            'category' => 'Class Not Found',
            'icon' => 'file-x',
            'suggestions' => [
                'Verify class imports and autoloading',
                'Run composer dump-autoload',
                'Check class namespace and file path',
            ],
        ],
        'Call to a member function .* on null' => [
            'category' => 'Null Pointer',
            'icon' => 'alert-triangle',
            'suggestions' => [
                'Check for null values before accessing properties/methods',
                'Verify object initialization',
                'Use optional() helper or null coalescing operator',
            ],
        ],
        'Undefined array key' => [
            'category' => 'Data Error',
            'icon' => 'list',
            'suggestions' => [
                'Validate array keys before accessing them',
                'Check job payload structure',
            ],
        ],
        'Division by zero' => [
            'category' => 'Logic Error',
            'icon' => 'divide',
            'suggestions' => [
                'Add checks for zero values in calculations',
                'Validate input data',
            ],
        ],

        // 2. Database Issues
        'SQLSTATE\[08006\]' => [
            'category' => 'Database Connection',
            'icon' => 'database',
            'suggestions' => [
                'Verify database host and port in .env',
                'Check if database server is running',
                'Ensure firewall allows connection from this server',
            ],
        ],
        'SQLSTATE\[HY000\] \[2002\]' => [
            'category' => 'Database Connection',
            'icon' => 'database',
            'suggestions' => [
                'Check if MySQL/Postgres service is running',
                'Verify socket path if using localhost',
                'Check network connectivity to database server',
            ],
        ],
        'SQLSTATE\[HY000\] \[2006\]' => [
            'category' => 'Database Gone Away',
            'icon' => 'database',
            'suggestions' => [
                'Database server gone away',
                'Increase wait_timeout in database config',
                'Check for long-running queries or large packets',
            ],
        ],
        'SQLSTATE\[40001\]' => [
            'category' => 'Database Deadlock',
            'icon' => 'refresh-cw',
            'suggestions' => [
                'Job was killed by database deadlock',
                'Queue Monitor will automatically retry if configured',
                'Consider reducing transaction size or locking scope',
            ],
        ],
        'SQLSTATE\[23000\]' => [
            'category' => 'Data Integrity',
            'icon' => 'alert-octagon',
            'suggestions' => [
                'Attempted to insert duplicate entry for unique key',
                'Check for race conditions in job processing',
                'Verify foreign key constraints exist',
            ],
        ],
        'SQLSTATE\[42S02\]' => [
            'category' => 'Schema Mismatch',
            'icon' => 'layout',
            'suggestions' => [
                'Table not found - Run migrations',
                'Check if table name matches model configuration',
            ],
        ],
        'SQLSTATE\[42S22\]' => [
            'category' => 'Schema Mismatch',
            'icon' => 'layout',
            'suggestions' => [
                'Column not found - Run migrations',
                'Check for typos in column names',
                'Verify model $fillable/$guarded properties',
            ],
        ],
        'SQLSTATE\[08004\]' => [
            'category' => 'Max Connections',
            'icon' => 'users',
            'suggestions' => [
                'Max connections reached',
                'Increase database connection limit',
                'Check for connection leaks',
            ],
        ],

        // 3. Memory Issues
        'Allowed memory size' => [
            'category' => 'Memory Exhausted',
            'icon' => 'cpu',
            'suggestions' => [
                'Job exceeded PHP memory limit',
                'Increase memory_limit in php.ini or job-specific config',
                'Review loops and object creation',
                'Implement chunking for large datasets',
            ],
        ],

        // 4. Timeout Issues
        'Maximum execution time' => [
            'category' => 'Timeout',
            'icon' => 'hourglass',
            'suggestions' => [
                'Job took too long to complete',
                'Increase max_execution_time in PHP config',
                'Increase timeout property in job class',
                'Optimize slow operations',
            ],
        ],
        'Lock wait timeout' => [
            'category' => 'Lock Timeout',
            'icon' => 'lock',
            'suggestions' => [
                'Review database locks and transaction duration',
                'Optimize queries holding locks',
            ],
        ],

        // 5. External Service Failures
        'cURL error 6' => [
            'category' => 'DNS Error',
            'icon' => 'globe',
            'suggestions' => [
                'Could not resolve host',
                'Check DNS configuration and network settings',
            ],
        ],
        'cURL error 7' => [
            'category' => 'Connection Refused',
            'icon' => 'wifi-off',
            'suggestions' => [
                'Target server refused connection',
                'Check if service is running on target port',
                'Verify firewall rules',
            ],
        ],
        'cURL error 28' => [
            'category' => 'Timeout',
            'icon' => 'clock',
            'suggestions' => [
                'Connection timed out',
                'Increase timeout settings for external API calls',
                'Check target server performance',
            ],
        ],
        'cURL error 60' => [
            'category' => 'SSL Error',
            'icon' => 'shield-off',
            'suggestions' => [
                'SSL certificate problem',
                'Verify SSL certificates are valid and up-to-date',
                'Check CA bundle configuration',
            ],
        ],
        '429 Too Many Requests' => [
            'category' => 'Rate Limit',
            'icon' => 'gauge',
            'suggestions' => [
                'API rate limit exceeded',
                'Implement exponential backoff',
                'Use queue throttling',
            ],
        ],

        // 6. File System Issues
        'No such file or directory' => [
            'category' => 'File Not Found',
            'icon' => 'file',
            'suggestions' => [
                'Verify file paths and existence before operations',
                'Check for typos in paths',
            ],
        ],
        'Permission denied' => [
            'category' => 'Permission Error',
            'icon' => 'lock',
            'suggestions' => [
                'Check file/directory permissions (755/644)',
                'Verify user/group ownership',
                'Check write permissions',
            ],
        ],
        'No space left on device' => [
            'category' => 'Disk Full',
            'icon' => 'hard-drive',
            'suggestions' => [
                'Disk space full',
                'Monitor and clean up disk space',
                'Check log file sizes',
            ],
        ],

        // 7. Queue Configuration Issues
        'Serialization of .* is not allowed' => [
            'category' => 'Serialization Error',
            'icon' => 'package',
            'suggestions' => [
                'Avoid passing unserializable objects (closures, resources)',
                'Use SerializesModels trait correctly',
                'Pass IDs instead of full objects where possible',
            ],
        ],

        // 8. Model/Eloquent Issues
        'ModelNotFoundException' => [
            'category' => 'Missing Data',
            'icon' => 'search',
            'suggestions' => [
                'The requested model ID does not exist',
                'Check if the record was deleted before job ran',
                'Verify the ID passed in job payload is correct',
            ],
        ],
        'MassAssignmentException' => [
            'category' => 'Code Error',
            'icon' => 'shield',
            'suggestions' => [
                'Attempting to mass assign protected attribute',
                'Add attribute to $fillable in the Model',
                'Or use forceFill() if intended',
            ],
        ],
        'RelationNotFoundException' => [
            'category' => 'Code Error',
            'icon' => 'link',
            'suggestions' => [
                'Relationship not defined on the model',
                'Check for typos in relationship name',
                'Verify relationship method visibility (must be public)',
            ],
        ],

        // 9. Dependency/Service Issues
        'BindingResolutionException' => [
            'category' => 'Dependency Error',
            'icon' => 'box',
            'suggestions' => [
                'Service container binding not found',
                'Register services in service provider',
                'Check constructor dependencies',
            ],
        ],
        'CircularDependencyException' => [
            'category' => 'Dependency Error',
            'icon' => 'refresh-ccw',
            'suggestions' => [
                'Circular dependency detected',
                'Refactor to break circular dependencies',
                'Use setter injection instead of constructor injection',
            ],
        ],

        // 10. Validation/Data Issues
        'ValidationException' => [
            'category' => 'Validation',
            'icon' => 'check-square',
            'suggestions' => [
                'Job payload failed validation rules',
                'Check the specific validation errors in exception message',
                'Review data passed to the job',
            ],
        ],

        // 11. Redis/Cache Issues
        'RedisException' => [
            'category' => 'Redis Error',
            'icon' => 'server',
            'suggestions' => [
                'Check Redis server status and credentials',
                'Verify Redis connection configuration',
                'Check for Redis out of memory',
            ],
        ],

        // 12. Email/Notification Issues
        'Swift_TransportException' => [
            'category' => 'Email Error',
            'icon' => 'mail',
            'suggestions' => [
                'SMTP connection failed',
                'Verify SMTP credentials and server',
                'Check mail provider status',
            ],
        ],
        'Symfony\\\\Component\\\\Mailer\\\\Exception\\\\TransportException' => [
            'category' => 'Email Error',
            'icon' => 'mail',
            'suggestions' => [
                'SMTP connection failed',
                'Verify SMTP credentials and server',
                'Check mail provider status',
            ],
        ],

        // 14. Permission/Authorization Issues
        'AuthenticationException' => [
            'category' => 'Authentication',
            'icon' => 'lock',
            'suggestions' => [
                'Authentication expired or invalid',
                'Refresh tokens or re-authenticate',
                'Check API credentials',
            ],
        ],
        'AuthorizationException' => [
            'category' => 'Authorization',
            'icon' => 'shield',
            'suggestions' => [
                'Insufficient permissions',
                'Check policies and gates',
                'Verify user roles/permissions',
            ],
        ],
    ];

    /**
     * Analyze a failure and provide smart insights.
     */
    public function analyzeFailure(QueueFailure $failure): array
    {
        $insights = [
            'category' => 'Unknown',
            'icon' => 'help-circle',
            'severity' => $this->calculateSeverity($failure),
            'suggestions' => [],
            'similar_pattern' => null,
            'auto_fix_available' => false,
            'quick_actions' => [],
            'confidence' => 'low', // Added confidence level
        ];

        // 1. Check for specific Laravel/PHP exceptions (High Confidence)
        $laravelMatch = $this->detectLaravelExceptions($failure);
        if ($laravelMatch) {
            $insights['category'] = $laravelMatch['category'];
            $insights['icon'] = $laravelMatch['icon'];
            $insights['suggestions'] = $laravelMatch['suggestions'];
            $insights['confidence'] = 'high';
        } 
        // 2. Fallback to Regex Pattern Matching (Medium Confidence)
        else {
            foreach ($this->exceptionPatterns as $pattern => $info) {
                if (preg_match("/$pattern/i", $failure->exception_message . ' ' . $failure->exception_class)) {
                    $insights['category'] = $info['category'];
                    $insights['icon'] = $info['icon'];
                    $insights['suggestions'] = $info['suggestions'];
                    $insights['confidence'] = 'medium';
                    break;
                }
            }
        }

        // Add context-specific suggestions
        $insights['suggestions'] = array_merge(
            $insights['suggestions'],
            $this->getContextualSuggestions($failure)
        );

        // Find similar resolved failures for learning
        $insights['similar_pattern'] = $this->findSimilarResolvedFailure($failure);

        // Determine available quick actions
        $insights['quick_actions'] = $this->getQuickActions($failure);

        return $insights;
    }

    /**
     * Detect specific Laravel exceptions for high-confidence insights.
     */
    protected function detectLaravelExceptions(QueueFailure $failure): ?array
    {
        // Map of Exception Class => Config Key in exceptionPatterns
        // This allows us to reuse the patterns defined above but match EXACTLY on class name
        $classMap = [
            'Illuminate\Database\Eloquent\ModelNotFoundException' => 'ModelNotFoundException',
            'Illuminate\Validation\ValidationException' => 'ValidationException',
            'Illuminate\Database\Eloquent\MassAssignmentException' => 'MassAssignmentException',
            'Illuminate\Database\Eloquent\RelationNotFoundException' => 'RelationNotFoundException',
        ];

        foreach ($classMap as $className => $key) {
            if ($failure->exception_class === $className || is_subclass_of($failure->exception_class, $className)) {
                if (isset($this->exceptionPatterns[$key])) {
                    return $this->exceptionPatterns[$key];
                }
            }
        }

        return null;
    }

    /**
     * Calculate severity based on failure characteristics.
     */
    protected function calculateSeverity(QueueFailure $failure): string
    {
        $score = 0;

        // Recurring failures are more severe
        if ($failure->is_recurring) {
            $score += 3;
        }

        // Check occurrence count
        $occurrenceCount = QueueFailure::where('group_hash', $failure->group_hash)->count();
        if ($occurrenceCount > 10) {
            $score += 3;
        } elseif ($occurrenceCount > 5) {
            $score += 2;
        } elseif ($occurrenceCount > 2) {
            $score += 1;
        }

        // Critical exceptions
        $criticalPatterns = ['SQLSTATE', 'Memory', '500', 'Connection refused'];
        foreach ($criticalPatterns as $pattern) {
            if (stripos($failure->exception_message, $pattern) !== false) {
                $score += 2;
                break;
            }
        }

        // Return severity level
        if ($score >= 6) return 'critical';
        if ($score >= 4) return 'high';
        if ($score >= 2) return 'medium';
        return 'low';
    }

    /**
     * Get contextual suggestions based on job and failure details.
     */
    protected function getContextualSuggestions(QueueFailure $failure): array
    {
        $suggestions = [];

        // If job has been retried multiple times
        if ($failure->retry_count >= 3) {
            $suggestions[] = '⚠️ This job has been retried ' . $failure->retry_count . ' times. Consider investigating root cause before retrying again.';
        }

        // If failure is in production
        if ($failure->environment === 'production') {
            $suggestions[] = '🚨 This is a production failure - prioritize investigation.';
        }

        // If exception contains stack trace insights
        if (stripos($failure->stack_trace ?? '', 'vendor/') !== false &&
            stripos($failure->stack_trace ?? '', 'app/') === false) {
            $suggestions[] = '📦 Error originates in vendor code - check package versions and compatibility.';
        }

        return $suggestions;
    }

    /**
     * Find similar failures that were resolved to learn from them.
     */
    protected function findSimilarResolvedFailure(QueueFailure $failure): ?array
    {
        $resolved = QueueFailure::where('group_hash', $failure->group_hash)
            ->whereNotNull('resolved_at')
            ->whereNotNull('resolution_notes')
            ->orderByDesc('resolved_at')
            ->first();

        if ($resolved) {
            return [
                'id' => $resolved->id,
                'resolved_at' => $resolved->resolved_at->diffForHumans(),
                'notes' => $resolved->resolution_notes,
            ];
        }

        return null;
    }

    /**
     * Get available quick actions for a failure.
     */
    protected function getQuickActions(QueueFailure $failure): array
    {
        $actions = [];

        // Always available
        $actions[] = [
            'label' => 'Retry Job',
            'icon' => 'refresh-cw',
            'action' => 'retry',
            'class' => 'btn-primary',
        ];

        if (!$failure->isResolved()) {
            $actions[] = [
                'label' => 'Mark Resolved',
                'icon' => 'check-circle',
                'action' => 'resolve',
                'class' => 'btn-success',
            ];
        }

        // Copy exception for reporting
        $actions[] = [
            'label' => 'Copy Error',
            'icon' => 'copy',
            'action' => 'copy-error',
            'class' => 'btn-ghost',
        ];

        return $actions;
    }

    /**
     * Get dashboard insights - smart summary for the main dashboard.
     */
    public function getDashboardInsights(): array
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $yesterday = $now->copy()->subDay()->startOfDay();
        $lastWeek = $now->copy()->subWeek();

        // Today's failures
        $todayCount = QueueFailure::where('failed_at', '>=', $today)->count();
        $yesterdayCount = QueueFailure::whereBetween('failed_at', [$yesterday, $today])->count();

        // Trend calculation
        $trend = 'stable';
        $trendPercent = 0;
        if ($yesterdayCount > 0) {
            $trendPercent = round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100);
            $trend = $trendPercent > 10 ? 'up' : ($trendPercent < -10 ? 'down' : 'stable');
        }

        // Critical issues needing attention
        $criticalCount = QueueFailure::where('is_recurring', true)
            ->whereNull('resolved_at')
            ->where('failed_at', '>=', $lastWeek)
            ->count();

        // Jobs that keep failing (problematic patterns)
        $problematicJobs = QueueFailure::select('job_name')
            ->selectRaw('COUNT(*) as failure_count')
            ->whereNull('resolved_at')
            ->where('failed_at', '>=', $lastWeek)
            ->groupBy('job_name')
            ->orderByDesc('failure_count')
            ->limit(3)
            ->get();

        // Auto-generated action items
        $actionItems = $this->generateActionItems();

        return [
            'today_count' => $todayCount,
            'yesterday_count' => $yesterdayCount,
            'trend' => $trend,
            'trend_percent' => $trendPercent,
            'critical_count' => $criticalCount,
            'problematic_jobs' => $problematicJobs,
            'action_items' => $actionItems,
            'last_failure' => QueueFailure::orderByDesc('failed_at')->first()?->failed_at?->diffForHumans() ?? 'No failures',
        ];
    }

    /**
     * Generate smart action items for the dashboard.
     */
    protected function generateActionItems(): Collection
    {
        $items = collect();

        // Check for unresolved recurring failures
        $recurringUnresolved = QueueFailure::where('is_recurring', true)
            ->whereNull('resolved_at')
            ->count();

        if ($recurringUnresolved > 0) {
            $items->push([
                'priority' => 'high',
                'icon' => 'repeat',
                'message' => "$recurringUnresolved recurring failures need attention",
                'action' => 'View recurring failures',
                'url' => '?recurring=1&unresolved=1',
            ]);
        }

        // Jobs with high retry counts
        $highRetryJobs = QueueFailure::where('retry_count', '>=', 3)
            ->whereNull('resolved_at')
            ->count();

        if ($highRetryJobs > 0) {
            $items->push([
                'priority' => 'medium',
                'icon' => 'alert-triangle',
                'message' => "$highRetryJobs jobs have failed after 3+ retries",
                'action' => 'Investigate',
                'url' => '?unresolved=1',
            ]);
        }

        // Same job failing on multiple queues
        $multiQueueFailures = QueueFailure::select('job_name')
            ->selectRaw('COUNT(DISTINCT queue) as queue_count')
            ->whereNull('resolved_at')
            ->where('failed_at', '>=', Carbon::now()->subDay())
            ->groupBy('job_name')
            ->havingRaw('COUNT(DISTINCT queue) > 1')
            ->first();

        if ($multiQueueFailures) {
            $items->push([
                'priority' => 'medium',
                'icon' => 'layers',
                'message' => "'{$multiQueueFailures->job_name}' is failing across multiple queues",
                'action' => 'View details',
                'url' => "?search={$multiQueueFailures->job_name}",
            ]);
        }

        return $items;
    }

    /**
     * Get quick stats for display.
     */
    public function getQuickStats(): array
    {
        $now = Carbon::now();

        return [
            'last_hour' => QueueFailure::where('failed_at', '>=', $now->copy()->subHour())->count(),
            'last_24h' => QueueFailure::where('failed_at', '>=', $now->copy()->subDay())->count(),
            'unresolved' => QueueFailure::whereNull('resolved_at')->count(),
            'recurring' => QueueFailure::where('is_recurring', true)->whereNull('resolved_at')->count(),
            'resolution_rate' => $this->calculateResolutionRate(),
        ];
    }

    /**
     * Calculate resolution rate percentage.
     */
    protected function calculateResolutionRate(): int
    {
        $total = QueueFailure::where('failed_at', '>=', Carbon::now()->subWeek())->count();
        if ($total === 0) return 100;

        $resolved = QueueFailure::where('failed_at', '>=', Carbon::now()->subWeek())
            ->whereNotNull('resolved_at')
            ->count();

        return (int) round(($resolved / $total) * 100);
    }
}
