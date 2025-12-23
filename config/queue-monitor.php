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

    'alerts' => [
        'enabled' => env('QUEUE_MONITOR_ALERTS', true),
        // Address to send email alerts to (defaults to app mail.from)
        'mail_to' => env('QUEUE_MONITOR_MAIL_TO'),

        // Slack webhook URL for alerts
        'slack_webhook_url' => env('QUEUE_MONITOR_SLACK_WEBHOOK_URL'),

        // Minimum number of failures in the window before sending an alert
        'min_failures_for_alert' => env('QUEUE_MONITOR_MIN_FAILURES', 1),

        // Sliding time window (in minutes) to aggregate failures for alerting
        'window_minutes' => env('QUEUE_MONITOR_WINDOW_MINUTES', 5),

        // Do not send a new alert more often than this (in minutes)
        'throttle_minutes' => env('QUEUE_MONITOR_THROTTLE_MINUTES', 5),
    ],

    'retention_days' => 90,

    'dashboard' => [
        'title' => 'Queue Monitor',
        'health_score_enabled' => true,
        // Auto-refresh interval in seconds for the dashboard (0 = disabled)
        'auto_refresh_seconds' => env('QUEUE_MONITOR_AUTO_REFRESH', 10),
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
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configure data export functionality.
    |
    */
    'export' => [
        // Maximum number of records per export
        'max_records' => 10000,
    ],
];


