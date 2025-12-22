<?php

namespace NikunjKothiya\QueueMonitor\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;

class AlertService
{
    public function shouldNotify(QueueFailure $failure): bool
    {
        if (! config('queue-monitor.alerts.enabled')) {
            return false;
        }

        // Only alert in configured environments if set
        $envs = config('queue-monitor.enabled_environments');
        if (is_array($envs) && ! in_array($failure->environment, $envs, true)) {
            return false;
        }

        $windowMinutes = (int) config('queue-monitor.alerts.window_minutes', 5);
        $minFailures = (int) config('queue-monitor.alerts.min_failures_for_alert', 1);

        $from = Carbon::now()->subMinutes($windowMinutes);

        $recentCount = QueueFailure::where('failed_at', '>=', $from)->count();
        if ($recentCount < $minFailures) {
            return false;
        }

        $throttleMinutes = (int) config('queue-monitor.alerts.throttle_minutes', 5);
        $key = $this->cacheKey();
        $lastAlertAt = Cache::get($key);

        if ($lastAlertAt instanceof Carbon) {
            if ($lastAlertAt->greaterThan(Carbon::now()->subMinutes($throttleMinutes))) {
                return false;
            }
        }

        return true;
    }

    public function recordNotification(): void
    {
        Cache::put($this->cacheKey(), Carbon::now(), now()->addDay());
    }

    protected function cacheKey(): string
    {
        return 'queue-monitor:last-alert';
    }
}


