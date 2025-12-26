<?php

return [
    'enabled' => env('QUEUE_MONITOR_ENABLED', true),

    'route_prefix' => 'queue-monitor',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware are applied to all Queue Monitor routes.
    | By default we only use "web" so the package also works in apps
    | without authentication or a login route.
    |
    | In apps with authentication, you can change this to:
    | ['web', 'auth', 'queue-monitor']
    | to require login + the viewQueueMonitor gate.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Email and Slack alerting with smart throttling and filtering.
    |
    */
    'alerts' => [
        'enabled' => env('QUEUE_MONITOR_ALERTS', true),
        
        // Email notifications
        'mail_to' => env('QUEUE_MONITOR_MAIL_TO'),

        // Slack webhook URL
        'slack_webhook_url' => env('QUEUE_MONITOR_SLACK_WEBHOOK_URL'),

        // Minimum number of failures before sending an alert
        'min_failures_for_alert' => env('QUEUE_MONITOR_MIN_FAILURES', 1),

        // Sliding time window (in minutes) to aggregate failures
        'window_minutes' => env('QUEUE_MONITOR_WINDOW_MINUTES', 5),

        // Per-group throttle: don't alert for same error more often than this
        'throttle_minutes' => env('QUEUE_MONITOR_THROTTLE_MINUTES', 5),
        
        // Global cooldown: minimum seconds between ANY alerts (prevents storms)
        'global_cooldown_seconds' => env('QUEUE_MONITOR_GLOBAL_COOLDOWN', 30),
        
        // Minimum priority score to trigger alert (0-100, higher = more critical)
        'min_priority_score' => env('QUEUE_MONITOR_MIN_PRIORITY', 0),
        
        // Only alert for these environments (null = all environments)
        'environments' => null, // e.g., ['production', 'staging']
        
        // Only alert for these queues (null = all queues)
        'only_queues' => null, // e.g., ['payments', 'critical']
        
        // Never alert for these queues
        'except_queues' => [], // e.g., ['low-priority', 'batch']
    ],

    'retention_days' => 90,

    'dashboard' => [
        'title' => 'Queue Monitor',
        'health_score_enabled' => true,
        'auto_refresh_seconds' => env('QUEUE_MONITOR_AUTO_REFRESH', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Configuration (Optional)
    |--------------------------------------------------------------------------
    |
    | Priority is calculated AUTOMATICALLY based on:
    | - Queue name keywords (payment, email, webhook, etc.)
    | - Job class name keywords
    | - Exception severity
    | - Environment (production gets higher priority)
    |
    | You can optionally override with specific queue names below.
    | Leave empty to use automatic detection (recommended).
    |
    */
    'priority' => [
        // Optional: Force these queues to critical priority (90+)
        // Example: ['payments', 'billing']
        'critical_queues' => [],
        
        // Optional: Force these queues to high priority (70+)
        // Example: ['notifications', 'emails']
        'high_queues' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Settings
    |--------------------------------------------------------------------------
    |
    | Configure how recurring failures are detected and tracked.
    |
    */
    'analytics' => [
        // Number of failures in the window to mark as recurring
        'recurring_threshold' => env('QUEUE_MONITOR_RECURRING_THRESHOLD', 3),
        // Time window in hours for counting recurring failures
        'recurring_window_hours' => env('QUEUE_MONITOR_RECURRING_WINDOW', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Exception Patterns
    |--------------------------------------------------------------------------
    |
    | Add your own exception patterns for domain-specific error recognition.
    | These are merged with the built-in patterns.
    |
    */
    'custom_patterns' => [
        // Example:
        // 'PaymentGatewayException' => [
        //     'category' => 'Payment Error',
        //     'icon' => 'credit-card',
        //     'suggestions' => [
        //         'Check payment gateway credentials',
        //         'Verify API endpoint is accessible',
        //         'Review transaction limits',
        //     ],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configure data export functionality.
    |
    */
    'export' => [
        'max_records' => 10000,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Fine-tune performance for high-volume environments.
    |
    */
    'performance' => [
        // Use cache for recurring detection (recommended for high volume)
        'use_cache_for_recurring' => true,
        
        // Cache driver for queue monitor data (null = default cache)
        'cache_driver' => null,
        
        // Batch size for background analytics computation
        'analytics_batch_size' => 1000,
    ],
];
