# Laravel Queue Monitor

A self-hosted queue failure monitoring dashboard for Laravel 10, 11 & 12 applications.

---

## ✨ Features

### 🧠 Smart Insights
- **30+ Exception Patterns** – Automatically detects and categorizes failures (ModelNotFound, Deadlocks, Timeouts, Rate Limits, etc.)
- **Severity Scoring** – Critical/High/Medium/Low based on impact
- **Actionable Suggestions** – Context-aware fix recommendations
- **Historical Learning** – Shows how similar failures were resolved

### 🔄 Smart Retry with Payload Editor
- **Edit & Retry** – Modify job properties before retrying (fix IDs, emails, etc.)
- **Smart Property Editor** – User-friendly UI for editing job properties
- **Raw JSON Editor** – Advanced mode for full payload editing
- **Correct Queue Dispatch** – Jobs retry on their ORIGINAL connection and queue
- **Audit Trail** – Track who retried with what modifications

### 🚨 Smart Alert Throttling
- **Email + Slack** – Multi-channel notifications
- **Cooldown** – Minimum time between alerts for same error (prevents spam)
- **Window** – Time window to count failures before alerting
- **Min Failures** – Require X failures before sending alert
- **Queue-Specific Rules** – Alert only for specific queues
- **Priority-Based** – Only alert for high-priority failures

### 🎯 Automatic Priority Detection
- **Zero Configuration** – Priority detected automatically from queue name, job class, and exception type
- **50+ Keywords** recognized (payment, billing, email, webhook, sync, etc.)

### 🔍 Filters & Export
- Filter by Queue, Connection, Environment, Date Range
- Export to CSV or JSON

---

## 📦 Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, or 12.x

---

## 📦 Installation

### 1. Install via Composer

```bash
composer require nikunjkothiya/laravel-queue-monitor
```

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=queue-monitor-config
```

---

## ⚙️ Configuration

### Environment Variables

```env
# Enable/disable monitoring
QUEUE_MONITOR_ENABLED=true

# Email notifications
QUEUE_MONITOR_MAIL_TO=devops@example.com

# Slack notifications
QUEUE_MONITOR_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/XXX/YYY/ZZZ

# Alert throttling
QUEUE_MONITOR_MIN_FAILURES=1        # Minimum failures before alerting
QUEUE_MONITOR_WINDOW_MINUTES=5      # Time window to count failures
QUEUE_MONITOR_THROTTLE_MINUTES=5    # Cooldown between alerts for same error

# Dashboard
QUEUE_MONITOR_AUTO_REFRESH=10       # Auto-refresh seconds (0 = disabled)
```

### Full Configuration

```php
// config/queue-monitor.php
return [
    'enabled' => env('QUEUE_MONITOR_ENABLED', true),
    'route_prefix' => 'queue-monitor',
    'middleware' => ['web'],  // Add 'auth' for protected access

    'alerts' => [
        'enabled' => env('QUEUE_MONITOR_ALERTS', true),
        'mail_to' => env('QUEUE_MONITOR_MAIL_TO'),
        'slack_webhook_url' => env('QUEUE_MONITOR_SLACK_WEBHOOK_URL'),
        
        // Throttling
        'min_failures_for_alert' => env('QUEUE_MONITOR_MIN_FAILURES', 1),
        'window_minutes' => env('QUEUE_MONITOR_WINDOW_MINUTES', 5),
        'throttle_minutes' => env('QUEUE_MONITOR_THROTTLE_MINUTES', 5),
        
        // Filters
        'min_priority_score' => 0,
        'only_queues' => null,
        'except_queues' => [],
        'environments' => null,
    ],

    'retention_days' => 90,
    
    'dashboard' => [
        'auto_refresh_seconds' => env('QUEUE_MONITOR_AUTO_REFRESH', 10),
    ],
];
```

---

## 🔒 Authorization

Protect the dashboard:

```php
// config/queue-monitor.php
'middleware' => ['web', 'auth'],
```

For role-based access:

```php
// AppServiceProvider.php
Gate::define('viewQueueMonitor', fn ($user) => $user->is_admin);

// config/queue-monitor.php
'middleware' => ['web', 'auth', 'can:viewQueueMonitor'],
```

---

## 🚀 Usage

### Dashboard

Visit `/queue-monitor` to see health score, failure charts, and recent failures.

### Retry Failed Jobs

**Simple Retry:** Click "Retry Job" to re-dispatch to the original queue.

**Edit & Retry:**
1. Open "Edit Payload & Retry"
2. Modify properties using Smart Editor or Raw JSON
3. Click "Retry with Modified Data"

Jobs dispatch to their **original connection and queue** (Redis, Database, SQS, etc.).

---

## 📊 How Alert Throttling Works

| Setting | Description |
|---------|-------------|
| `min_failures_for_alert` | Number of failures required before sending alert |
| `window_minutes` | Time window to count failures |
| `throttle_minutes` | Cooldown - don't repeat alert for same error |

**Example:** With `min_failures: 3`, `window: 5`, `throttle: 15`:
- Alert when same error occurs 3+ times in 5 minutes
- Don't repeat alert for that error for 15 minutes

---

## � Arttisan Commands

```bash
# Prune old records
php artisan queue-monitor:prune --days=90

# Compute analytics
php artisan queue-monitor:compute-analytics
```

### Scheduler Setup

**Laravel 11+** (`routes/console.php`):
```php
Schedule::command('queue-monitor:compute-analytics')->everyFiveMinutes();
Schedule::command('queue-monitor:prune --days=90')->daily();
```

**Laravel 10** (`app/Console/Kernel.php`):
```php
$schedule->command('queue-monitor:compute-analytics')->everyFiveMinutes();
$schedule->command('queue-monitor:prune --days=90')->daily();
```

---

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 💖 Support

If this package helps you, consider supporting development:

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-Support-orange.svg?style=flat-square&logo=buy-me-a-coffee)](https://buymeacoffee.com/nikunjkothiya)

<img src="https://raw.githubusercontent.com/nikunjkothiya/assets/main/qr-code.png"
     alt="Buy Me A Coffee QR Code"
     width="180" />

---

## 📄 License

MIT License - see [LICENSE](LICENSE) for details.

---

<p align="center">
  Made with ❤️ by <a href="https://github.com/nikunjkothiya">Nikunj Kothiya</a>
</p>
