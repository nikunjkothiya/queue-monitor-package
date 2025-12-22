# Laravel Queue Monitor

<p align="center">
  <strong>Self-hosted queue failure monitoring and analytics dashboard for Laravel 11+ applications.</strong>
</p>

<p align="center">
  A premium, modern queue monitoring solution with dark mode, real-time dashboard, and intelligent alerting.
</p>

---

## ✨ Features

### 🎨 Modern Dashboard
- **Dark/Light Mode** – Toggle between themes with localStorage persistence
- **Glassmorphism UI** – Premium card designs with modern aesthetics
- **Health Score Ring** – Animated gauge showing overall queue health (0-100)
- **Real-time Charts** – Beautiful area charts for failures over time
- **Auto-refresh** – Configurable dashboard refresh with countdown indicator

### 📊 Queue Analytics
- **Total Failures** – Track failures in configurable time windows
- **Resolution Rate** – Monitor how quickly issues are resolved
- **Average Resolution Time** – Measure time from failure to resolution
- **Top Failing Jobs** – Identify problematic jobs at a glance
- **Queue Driver Diagnostics** – Health checks for Redis, Database, SQS, and Sync drivers

### 🔧 Failure Management
- **Search & Filter** – Find failures by job name
- **Bulk Actions** – Resolve multiple failures at once
- **Retry Jobs** – Re-dispatch failed jobs with retry count tracking
- **Resolution Notes** – Document how issues were resolved
- **Timeline View** – Visual job lifecycle from failure to resolution
- **Copy-to-Clipboard** – Easily copy stack traces and payloads

### 🚨 Smart Alert Throttling
- **Email + Slack Alerts** – Multi-channel notifications on failure bursts
- **Sliding Window** – Count failures in configurable time windows
- **Cooldown Period** – Prevent alert spam during incidents
- **Environment Filtering** – Alert only in specific environments

### 🔌 Queue Driver Support
- **Redis** – In-memory queue driver
- **Database** – MySQL, PostgreSQL queues
- **Amazon SQS** – AWS managed queues
- **Sync** – Development synchronous driver

---

## 📦 Installation

### 1. Require the Package

Until published on Packagist, install directly from GitHub. Add to your `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/nikunjkothiya/queue-monitor-package"
    }
  ]
}
```

Then require the package:

```bash
composer require nikunjkothiya/laravel-queue-monitor:dev-main
```

### 2. Run the Install Command

```bash
php artisan queue-monitor:install
```

This publishes:
- Configuration file
- Database migrations
- View files (optional, for customization)

### 3. Run Migrations

```bash
php artisan migrate
```

---

## ⚙️ Configuration

Publish and customize `config/queue-monitor.php`:

```php
return [
    // Enable/disable the package globally
    'enabled' => env('QUEUE_MONITOR_ENABLED', true),

    // Dashboard URL prefix
    'route_prefix' => 'queue-monitor',

    // Route middleware
    'middleware' => ['web'],  // Add 'auth' for protected access

    // Alert settings
    'alerts' => [
        'enabled' => env('QUEUE_MONITOR_ALERTS', true),
        'mail_to' => env('QUEUE_MONITOR_MAIL_TO'),
        'slack_webhook_url' => env('QUEUE_MONITOR_SLACK_WEBHOOK_URL'),
        'min_failures_for_alert' => env('QUEUE_MONITOR_MIN_FAILURES', 1),
        'window_minutes' => env('QUEUE_MONITOR_WINDOW_MINUTES', 5),
        'throttle_minutes' => env('QUEUE_MONITOR_THROTTLE_MINUTES', 5),
    ],

    // Data retention
    'retention_days' => 90,

    // Dashboard settings
    'dashboard' => [
        'title' => 'Queue Monitor',
        'health_score_enabled' => true,
        'auto_refresh_seconds' => env('QUEUE_MONITOR_AUTO_REFRESH', 10),
    ],
];
```

### Environment Variables

```env
QUEUE_MONITOR_ENABLED=true

# Alert notifications
QUEUE_MONITOR_MAIL_TO=devops@example.com
QUEUE_MONITOR_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/XXX/YYY/ZZZ

# Alert throttling
QUEUE_MONITOR_MIN_FAILURES=3
QUEUE_MONITOR_WINDOW_MINUTES=5
QUEUE_MONITOR_THROTTLE_MINUTES=5

# Dashboard auto-refresh (seconds, 0 = disabled)
QUEUE_MONITOR_AUTO_REFRESH=10
```

---

## 🔒 Authorization

The package provides a `queue-monitor` middleware for access control.

### Public Access (Default)

The dashboard is accessible without authentication by default.

### Protected Access

To require authentication:

1. Update your config:

```php
'middleware' => ['web', 'auth', 'queue-monitor'],
```

2. Define the `viewQueueMonitor` gate:

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewQueueMonitor', fn ($user) => $user->is_admin);
}
```

---

## 📖 Usage

### Dashboard

Visit `https://your-app.test/queue-monitor` to see:

- **Stats Cards** – Total failures, unresolved count, resolution rate, avg resolution time
- **Health Score** – Animated ring showing queue health (0-100)
- **Failures Chart** – Area chart of failures over time
- **Driver Status** – Which queue drivers are configured
- **Recent Failures** – Quick access to latest issues
- **Alert Config** – Current throttling settings

### Failed Jobs List

Navigate to `queue-monitor/failures`:

- **Search** – Filter by job name
- **Unresolved Filter** – Show only unresolved failures
- **Bulk Resolve** – Select multiple and mark resolved
- **Clear All** – Remove all records (with confirmation)

### Failure Detail

Click any failure to see:

- **Exception Message** – With copy-to-clipboard
- **Stack Trace** – Collapsible, with syntax highlighting
- **Job Payload** – Collapsible JSON view
- **Retry Button** – Re-dispatch the job
- **Resolve Form** – Mark resolved with notes
- **Timeline** – Visual job lifecycle

---

## 🛠️ Artisan Commands

### Prune Old Failures

```bash
php artisan queue-monitor:prune --days=90
```

### Compute Analytics

```bash
php artisan queue-monitor:compute-analytics
```

---

## 🎯 Queue Health Score

The health score (0-100) is computed from:

| Factor | Impact |
|--------|--------|
| Unresolved vs Total | Up to 60 points penalty |
| Recent Failures (7 days) | Up to 40 points penalty |

**Interpretation:**
- **80-100** – Healthy ✅
- **50-79** – Warning ⚠️
- **0-49** – Critical 🔴

---

## 🗄️ Database Schema

The `queue_failures` table includes:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| uuid | uuid | Unique identifier |
| connection | string | Queue connection name |
| queue | string | Queue name |
| job_name | string | Job class name |
| payload | longtext | Serialized job payload |
| exception_message | text | Error message |
| stack_trace | longtext | Full stack trace |
| failed_at | timestamp | When the job failed |
| environment | string | App environment |
| resolved_at | timestamp | When resolved |
| resolution_notes | text | Resolution description |
| resolved_by | bigint | User ID who resolved |
| retry_count | int | Number of retry attempts |
| last_retried_at | timestamp | Last retry timestamp |

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Submit a pull request

**Branch Policy:**
- `main` – Stable releases
- `dev` – Development (may have breaking changes)

---

## 📄 License

This package is open-source software licensed under the **MIT license**.

---

<p align="center">
  Made with ❤️ by <a href="https://github.com/nikunjkothiya">Nikunj Kothiya</a>
</p>
