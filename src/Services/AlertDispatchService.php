<?php

namespace NikunjKothiya\QueueMonitor\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;
use NikunjKothiya\QueueMonitor\Notifications\QueueFailureNotification;

/**
 * Smart alert dispatching with queue-specific rules.
 * Supports Email and Slack notifications.
 */
class AlertDispatchService
{
    /**
     * Determine if we should alert for this failure and dispatch if yes.
     */
    public function processFailure(QueueFailure $failure): bool
    {
        if (!config('queue-monitor.alerts.enabled', true)) {
            return false;
        }
        
        // Check queue-specific rules first
        if (!$this->shouldAlertForQueue($failure->queue)) {
            return false;
        }
        
        // Check environment rules
        if (!$this->shouldAlertForEnvironment($failure->environment)) {
            return false;
        }
        
        // Check priority threshold
        if (!$this->meetsAlertPriority($failure)) {
            return false;
        }
        
        // Check throttling (per group_hash for accuracy)
        if ($this->isThrottled($failure)) {
            return false;
        }
        
        // Check minimum failures threshold
        if (!$this->meetsMinimumThreshold($failure)) {
            return false;
        }
        
        // Dispatch alerts
        return $this->dispatch($failure);
    }
    
    /**
     * Check if queue is configured for alerts.
     */
    protected function shouldAlertForQueue(?string $queue): bool
    {
        $onlyQueues = config('queue-monitor.alerts.only_queues');
        $exceptQueues = config('queue-monitor.alerts.except_queues', []);
        
        // If only_queues is set, queue must be in the list
        if (is_array($onlyQueues) && !empty($onlyQueues)) {
            return in_array($queue, $onlyQueues, true);
        }
        
        // If except_queues is set, queue must NOT be in the list
        if (!empty($exceptQueues)) {
            return !in_array($queue, $exceptQueues, true);
        }
        
        return true;
    }
    
    /**
     * Check if environment is configured for alerts.
     */
    protected function shouldAlertForEnvironment(?string $environment): bool
    {
        $environments = config('queue-monitor.alerts.environments');
        
        if (is_array($environments) && !empty($environments)) {
            return in_array($environment, $environments, true);
        }
        
        // Default: alert in all environments
        return true;
    }
    
    /**
     * Check if failure meets minimum priority for alerting.
     */
    protected function meetsAlertPriority(QueueFailure $failure): bool
    {
        $minPriority = config('queue-monitor.alerts.min_priority_score', 0);
        
        return ($failure->priority_score ?? 50) >= $minPriority;
    }
    
    /**
     * Check if this failure group is currently throttled.
     * Uses per-group throttling for better accuracy.
     */
    protected function isThrottled(QueueFailure $failure): bool
    {
        $throttleMinutes = config('queue-monitor.alerts.throttle_minutes', 5);
        
        // Global throttle key
        $globalKey = 'qm:alert:global';
        
        // Per-group throttle key (more granular)
        $groupKey = "qm:alert:group:{$failure->group_hash}";
        
        // Check global throttle first (prevents alert storms)
        $globalLastAlert = Cache::get($globalKey);
        if ($globalLastAlert instanceof Carbon) {
            $globalCooldown = config('queue-monitor.alerts.global_cooldown_seconds', 30);
            if ($globalLastAlert->diffInSeconds(now()) < $globalCooldown) {
                return true;
            }
        }
        
        // Check per-group throttle
        $groupLastAlert = Cache::get($groupKey);
        if ($groupLastAlert instanceof Carbon) {
            if ($groupLastAlert->greaterThan(now()->subMinutes($throttleMinutes))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if minimum failure threshold is met.
     */
    protected function meetsMinimumThreshold(QueueFailure $failure): bool
    {
        $minFailures = config('queue-monitor.alerts.min_failures_for_alert', 1);
        
        if ($minFailures <= 1) {
            return true;
        }
        
        // Use cached counter for performance
        $count = app(FailureIngestionService::class)->getGroupCount($failure->group_hash);
        
        return $count >= $minFailures;
    }
    
    /**
     * Dispatch alerts to configured channels (Email and Slack).
     */
    protected function dispatch(QueueFailure $failure): bool
    {
        $dispatched = false;
        
        // Email
        if ($this->dispatchEmail($failure)) {
            $dispatched = true;
        }
        
        // Slack
        if ($this->dispatchSlack($failure)) {
            $dispatched = true;
        }
        
        // Record throttle timestamps
        if ($dispatched) {
            $this->recordAlertSent($failure);
        }
        
        return $dispatched;
    }
    
    /**
     * Dispatch email notification.
     */
    protected function dispatchEmail(QueueFailure $failure): bool
    {
        $mailTo = config('queue-monitor.alerts.mail_to') ?: config('mail.from.address');
        
        if (!$mailTo) {
            return false;
        }
        
        try {
            Notification::route('mail', $mailTo)
                ->notify(new QueueFailureNotification($failure));
            return true;
        } catch (\Throwable $e) {
            Log::warning('Queue Monitor: Failed to send email alert', [
                'error' => $e->getMessage(),
                'failure_id' => $failure->id,
            ]);
            return false;
        }
    }
    
    /**
     * Dispatch Slack notification.
     */
    protected function dispatchSlack(QueueFailure $failure): bool
    {
        $webhookUrl = config('queue-monitor.alerts.slack_webhook_url');
        
        if (!$webhookUrl) {
            return false;
        }
        
        try {
            $priorityEmoji = $this->getPriorityEmoji($failure);
            $color = $this->getSeverityColor($failure);
            
            $response = Http::timeout(5)->post($webhookUrl, [
                'text' => "{$priorityEmoji} *Queue Failure Alert*",
                'attachments' => [
                    [
                        'color' => $color,
                        'title' => $failure->job_name,
                        'text' => $this->truncateMessage($failure->exception_message, 500),
                        'fields' => [
                            ['title' => 'Queue', 'value' => $failure->queue ?? 'default', 'short' => true],
                            ['title' => 'Environment', 'value' => $failure->environment ?? 'unknown', 'short' => true],
                            ['title' => 'Exception', 'value' => class_basename($failure->exception_class), 'short' => true],
                            ['title' => 'Priority', 'value' => $failure->getPriorityLabel(), 'short' => true],
                        ],
                        'footer' => 'Queue Monitor',
                        'ts' => $failure->failed_at?->timestamp,
                    ],
                ],
            ]);
            
            // Check if response was successful (2xx status code)
            return $response->ok();
        } catch (\Throwable $e) {
            Log::warning('Queue Monitor: Failed to send Slack alert', [
                'error' => $e->getMessage(),
                'failure_id' => $failure->id,
            ]);
            return false;
        }
    }
    
    /**
     * Record that an alert was sent for throttling.
     */
    protected function recordAlertSent(QueueFailure $failure): void
    {
        $throttleMinutes = config('queue-monitor.alerts.throttle_minutes', 5);
        
        Cache::put('qm:alert:global', now(), now()->addSeconds(60));
        Cache::put("qm:alert:group:{$failure->group_hash}", now(), now()->addMinutes($throttleMinutes));
    }
    
    /**
     * Get priority emoji for Slack message.
     */
    protected function getPriorityEmoji(QueueFailure $failure): string
    {
        $score = $failure->priority_score ?? 50;
        
        return match (true) {
            $score >= 80 => '🔴',
            $score >= 60 => '🟠',
            $score >= 40 => '🟡',
            default => '⚪',
        };
    }
    
    /**
     * Get severity color for Slack attachment.
     */
    protected function getSeverityColor(QueueFailure $failure): string
    {
        $score = $failure->priority_score ?? 50;
        
        return match (true) {
            $score >= 80 => '#dc3545', // Red
            $score >= 60 => '#fd7e14', // Orange
            $score >= 40 => '#ffc107', // Yellow
            default => '#6c757d',      // Gray
        };
    }
    
    /**
     * Truncate message to specified length.
     */
    protected function truncateMessage(string $message, int $maxLength): string
    {
        if (strlen($message) <= $maxLength) {
            return $message;
        }
        
        return substr($message, 0, $maxLength - 3) . '...';
    }
}
