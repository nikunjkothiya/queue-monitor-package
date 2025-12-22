## Laravel Queue Monitor

Self-hosted queue failure monitoring and analytics dashboard for Laravel 11+ applications.

### Features

- **Queue drivers**: Works with Laravel queues using **Redis**, **Database**, **SQS**, and **Sync** drivers.
- **Dashboard**:
  - Total failures and unresolved failures (last N days).
  - **Queue Health Score** (0–100) based on resolution rate, backlog, and recent failure trends.
  - Resolution rate and average resolution time.
  - Failures-over-time chart and top failing jobs.
  - Recent failures feed with status (New / Requiring attention / Resolved).
- **Failure management**:
  - Stores failed jobs in `queue_failures` table with payload, exception, stack trace, environment, and resolver info.
  - Detail page per failure with retry, resolve, and resolution notes.
  - Bulk resolve from the failures list.
- **Smart Alert Throttling**:
  - Email + Slack alerts on failure bursts.
  - Sliding time window + minimum failures threshold.
  - Cooldown window to avoid alert spam.
- **Queue driver diagnostics**:
  - Detects misconfiguration of the default queue connection (`QUEUE_CONNECTION`).
  - Highlights missing Redis/Database/SQS settings so developers can fix env issues.
- **Artisan commands**:
  - `queue-monitor:prune` to prune old failures.
  - `queue-monitor:compute-analytics` to precompute analytics (optional).
  - Optional UI button to **clear all records** from the `queue_failures` table when you need to reduce database size.

---

### Installation

1. **Require the package from GitHub**

Until this package is published on Packagist, you can install it directly from GitHub.

In your Laravel application's `composer.json`, add the repository:

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

Then require the package (use the **main** branch for stable installs):

```bash
composer require nikunjkothiya/laravel-queue-monitor:dev-main
```

- The `main` branch is the stable branch consumers should use.
- The `dev` branch is for ongoing development; avoid depending on it in production apps.

**Branch policy (important):**

- Always pull/install from **main** for stable code.
- **Do not** install from `dev` unless you are contributing and accept breaking changes.

2. **Run the install command (publishes assets + migrates)**

The easiest way to get started is to run the built-in install command:

```bash
php artisan queue-monitor:install
```

This will:

- Publish the package config file.
- Publish the migrations.
- Publish the views.
- Run `php artisan migrate`.

3. **Authorize access (restricting the dashboard URL)**

This package **only** protects its own routes (those under `/queue-monitor` by default) using a route middleware alias called `queue-monitor`. Your existing application routes are never wrapped by this middleware.

To control who can access the dashboard, define the `viewQueueMonitor` ability.  
You can do this either in your existing `App\Providers\AuthServiceProvider`, or (recommended) in a **dedicated provider** so the queue monitor logic stays separate.

Example dedicated provider (recommended):

```php
// app/Providers/QueueMonitorAuthServiceProvider.php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class QueueMonitorAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Only admins can view the queue monitor dashboard
        Gate::define('viewQueueMonitor', fn ($user) => $user->is_admin);
    }
}
```

Then register this provider in your `config/app.php` under `providers`:

```php
'providers' => [
    // ...
    App\Providers\QueueMonitorAuthServiceProvider::class,
],
```

How it works:

- The package routes are grouped with middleware: `['web', 'auth', 'queue-monitor']`.
- The `queue-monitor` middleware checks `auth()->user()->can('viewQueueMonitor')`.
- If the ability returns `false`, only the `/queue-monitor` URLs return **403 Forbidden**.
- Your other application URLs are not affected by this package.

If you want to make the dashboard public (for example, in a locked-down internal network), you can simply allow all users:

```php
Gate::define('viewQueueMonitor', fn ($user) => true);
```

If you prefer to do each step manually instead of using `queue-monitor:install`, you can still run:

```bash
php artisan vendor:publish --provider="NikunjKothiya\QueueMonitor\Providers\QueueMonitorServiceProvider" --tag=queue-monitor-config
php artisan vendor:publish --provider="NikunjKothiya\QueueMonitor\Providers\QueueMonitorServiceProvider" --tag=queue-monitor-migrations
php artisan vendor:publish --provider="NikunjKothiya\QueueMonitor\Providers\QueueMonitorServiceProvider" --tag=queue-monitor-views
php artisan migrate
```

---

### Configuration

Publish and review `config/queue-monitor.php`. Key options:

- **Basic**
  - **`enabled`**: Globally enable/disable the package.
  - **`route_prefix`**: Base URI for the dashboard (default: `queue-monitor`).
  - **`middleware`**: Middleware stack protecting the routes (default: `['web', 'auth', 'queue-monitor']`).
- **Alerts**
  - **`alerts.enabled`**: Turn alerts on or off.
  - **`alerts.mail_to`**: Email address that receives alerts (falls back to `mail.from.address`).
  - **`alerts.slack_webhook_url`**: Slack Incoming Webhook URL for alert messages.
  - **`alerts.min_failures_for_alert`**: Minimum number of failures in the window before sending an alert.
  - **`alerts.window_minutes`**: Size of the sliding time window (in minutes) to count failures.
  - **`alerts.throttle_minutes`**: Minimum time between alerts (cooldown) to prevent spam.
- **Retention & dashboard**
  - **`retention_days`**: How long failure records are kept before pruning.
  - **`dashboard.title`** / **`dashboard.health_score_enabled`**: UI preferences.

Environment variables you can set in `.env`:

```env
QUEUE_MONITOR_ENABLED=true

QUEUE_MONITOR_MAIL_TO=devops@example.com
QUEUE_MONITOR_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/XXX/YYY/ZZZ

QUEUE_MONITOR_MIN_FAILURES=3
QUEUE_MONITOR_WINDOW_MINUTES=5
QUEUE_MONITOR_THROTTLE_MINUTES=5
```

---

### Usage

#### Dashboard

- Visit `https://your-app.test/queue-monitor` (or the custom prefix you configured) while logged in.
- The dashboard shows:
  - Top summary cards: **Total Failures**, **Unresolved**, **Resolution Rate**, **Avg Resolution Time**.
  - Queue driver support strip (Redis, Database, SQS, Sync).
  - Failures-over-time chart and top failing jobs.
  - A **Queue Health Score** card describing current queue health.
  - A **Smart Alert Throttling** card describing your current alert configuration (cooldown, window, min failures).
  - Projects-style panel of “hot” jobs and a recent failures feed.

#### Failures list

- Go to `queue-monitor/failures`:
  - Filter unresolved failures.
  - Select multiple failures and **bulk mark as resolved**.
  - See status badges (Resolved / Unresolved).
  - Use the **“Clear all records”** button (with confirmation) to truncate all monitoring data when the table grows too large.

#### Failure detail

- Click any failure to open its detail page:
  - View metadata (queue, connection, environment, timestamps).
  - See exception message, stack trace, and raw payload.
  - **Retry** the failed job (reconstructs the job from serialized payload and re-dispatches it).
  - **Resolve** the failure with optional resolution notes; resolved records store `resolved_by` user and timestamps.

#### Artisan commands

- **Prune old failures**

```bash
php artisan queue-monitor:prune --days=90
```

If `--days` is omitted, it uses `retention_days` from config.

- **Compute analytics (optional)**

```bash
php artisan queue-monitor:compute-analytics
```

This touches analytics so you can warm caches or schedule it as a daily job if desired.

---

### Smart Alert Throttling (Details)

The package listens to `Illuminate\Queue\Events\JobFailed` events and records each failure in the database. For each failure:

1. **Alert window and threshold**
   - Counts how many failures occurred in the last `alerts.window_minutes` minutes.
   - If the count is **less** than `alerts.min_failures_for_alert`, no alert is sent.
2. **Throttle / cooldown**
   - Stores the timestamp of the last alert in cache.
   - If the last alert happened less than `alerts.throttle_minutes` ago, the alert is skipped.
3. **Multi-channel notification**
   - If alerts are allowed, a single `QueueFailureNotification` is sent to:
     - Email (`alerts.mail_to` or the app’s `mail.from.address`).
     - Slack (`alerts.slack_webhook_url`, via Incoming Webhooks).

This keeps alerts **accurate** (fire on real bursts) and **quiet** during large incidents (no spam).

---

### Queue Health Score (Details)

The **Queue Health Score** is a 0–100 metric computed from:

- Total failures vs. unresolved failures.
- Volume of failures in the recent window (last 7 days).
- Basic penalty for heavy recent failure activity.

High scores (80–100) indicate few unresolved failures and low recent failure volume, while low scores reflect backlog and instability. The score is visible on the dashboard and can be recomputed via the `queue-monitor:compute-analytics` command if you want to cache it.

---

### Database Schema

The package installs a `queue_failures` table with (simplified):

- `id`, `uuid`
- `connection`, `queue`, `job_name`
- `payload` (serialized job payload, nullable)
- `exception_message`, `stack_trace`
- `failed_at`, `environment`
- `resolved_at`, `resolution_notes`, `resolved_by`
- `created_at`, `updated_at`

You can customize or extend this migration when you publish it.

---

### License

This package is open-source software licensed under the **MIT license**.

