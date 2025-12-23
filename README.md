# Laravel Queue Monitor : Free Queue Monitoring Package

<p align="center">
  <strong>Self-hosted queue failure monitoring and analytics dashboard for Laravel 11+ applications.</strong>
</p>

<p align="center">
  A premium, modern queue monitoring solution with dark mode, real-time dashboard, and intelligent alerting.
</p>

---

## ✨ Features

### 🧠 Smart Insights (Expanded)
> **Intelligent failure analysis!**
- **Comprehensive Pattern Recognition** – Detects 30+ specific failure types including:
  - **Laravel Specifics**: `ModelNotFound`, `ValidationException`, `MassAssignment`, `RelationNotFound`.
  - **Database**: Deadlocks, Connection Refused, "Gone Away", Integrity violations.
  - **System**: Memory exhaustion, Disk full, File permissions.
  - **External**: DNS errors, SSL issues, Timeouts, Rate Limits (429).
- **Severity Scoring** – Critical/High/Medium/Low priority based on impact and recurrence.
- **Actionable Suggestions** – Context-aware fix recommendations for each error type.
- **Learn from History** – Shows how similar failures were resolved before.
- **Quick Actions** – One-click copy error, retry, and resolve.

### 🔄 Smart Retry with Payload Editor
- **Remote Retry** – Re-dispatch failed jobs directly from the dashboard.
- **Smart Property Editor** – Edit job properties (IDs, strings, booleans) with a user-friendly UI.
- **Raw JSON Editor** – Advanced mode to edit the full raw payload.
- **Syntax Validation** – Real-time JSON validation with error highlighting.
- **Audit Trail** – Track who retried with what modifications.

### 🚀 Bulk Operations 
- **Bulk Retry** – Re-dispatch multiple failed jobs at once.
- **Bulk Resolve** – Mark multiple failures as resolved.
- **Select All** – Quickly select all visible failures.

### 🔍 Advanced Filters 
- **Filter by Queue** – Isolate failures from specific queues.
- **Filter by Connection** – Database, Redis, SQS, etc.
- **Filter by Environment** – Production, staging, local.
- **Date Range** – Filter by failure date.
- **Recurring Only** – Show only recurring failure patterns.

### 📤 Export Functionality 
- **CSV Export** – Download failures as spreadsheet.
- **JSON Export** – Machine-readable format for automation.
- **Filter-aware** – Export respects current filter selections.

### 🎨 Modern Dashboard
- **Dark/Light Mode** – Toggle between themes with localStorage persistence.
- **Glassmorphism UI** – Premium card designs with modern aesthetics.
- **Health Score Ring** – Animated gauge showing overall queue health (0-100).
- **Real-time Charts** – Beautiful area charts for failures over time.
- **Auto-refresh** – Configurable dashboard refresh with countdown indicator.

### 🚨 Smart Alert Throttling
- **Email + Slack Alerts** – Multi-channel notifications on failure bursts.
- **Sliding Window** – Count failures in configurable time windows.
- **Cooldown Period** – Prevent alert spam during incidents.
- **Environment Filtering** – Alert only in specific environments.

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

### 2. Run Migrations

The package automatically registers its migrations. Just run:

```bash
php artisan migrate
```

### 3. Publish Configuration (Optional)

To customize the dashboard, alerts, or middleware:

```bash
php artisan vendor:publish --tag=queue-monitor-config
```

---

## ⚙️ Configuration

After publishing, customize `config/queue-monitor.php`:

```php
return [
    // Enable/disable the package globally
    'enabled' => env('QUEUE_MONITOR_ENABLED', true),

    // Dashboard URL prefix
    'route_prefix' => 'queue-monitor',

    // Route middleware - controlled entirely by this config
    'middleware' => ['web', 'auth'], 

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

To protect the dashboard, simply add the `auth` middleware to the config:

```php
// config/queue-monitor.php
'middleware' => ['web', 'auth', 'queue-monitor'],
```

Then define the `viewQueueMonitor` gate in your `AppServiceProvider` or `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewQueueMonitor', fn ($user) => $user->is_admin);
}
```

---

## 📖 Usage

### Dashboard
Visit `https://your-app.test/queue-monitor` to see the health score, charts, and recent failures.

### Failure Detail & Smart Retry
Click any failure to see the "Smart Insights" analysis.
- **Retry**: Click "Retry" to re-dispatch immediately.
- **Edit & Retry**: Use the "Smart Editor" to modify job properties (e.g., fix a typo in an email address or ID) before retrying.

### Artisan Commands

#### Prune Old Failures
```bash
php artisan queue-monitor:prune --days=90
```

#### Compute Analytics
```bash
php artisan queue-monitor:compute-analytics
```

---

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Submit a pull request

---

## 📄 License

This package is open-source software licensed under the **MIT license**.

---

<p align="center">
  Made with ❤️ by <a href="https://github.com/nikunjkothiya">Nikunj Kothiya</a>
</p>
