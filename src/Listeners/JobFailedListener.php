<?php

namespace NikunjKothiya\QueueMonitor\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Notification;
use NikunjKothiya\QueueMonitor\Services\AlertService;
use NikunjKothiya\QueueMonitor\Models\QueueFailure;
use NikunjKothiya\QueueMonitor\Notifications\QueueFailureNotification;

class JobFailedListener
{
    public function __construct(
        protected AlertService $alerts
    ) {
    }

    public function handle(JobFailed $event): void
    {
        $payload = $event->job?->getRawBody();

        $failure = QueueFailure::create([
            'connection' => $event->connectionName,
            'queue' => $event->job?->getQueue(),
            'job_name' => $this->resolveJobName($event),
            'payload' => $payload,
            'exception_message' => $event->exception->getMessage(),
            'stack_trace' => $event->exception->getTraceAsString(),
            'environment' => app()->environment(),
            'failed_at' => now(),
        ]);

        if ($this->alerts->shouldNotify($failure)) {
            $routes = [];

            $mailTo = config('queue-monitor.alerts.mail_to') ?: config('mail.from.address');
            if ($mailTo) {
                $routes['mail'] = $mailTo;
            }

            $slackWebhook = config('queue-monitor.alerts.slack_webhook_url');
            if ($slackWebhook) {
                $routes['slack'] = $slackWebhook;
            }

            if (! empty($routes)) {
                $notification = new QueueFailureNotification($failure);

                $notificationRoute = Notification::route('mail', $routes['mail'] ?? null);
                if (isset($routes['slack'])) {
                    $notificationRoute->route('slack', $routes['slack']);
                }

                $notificationRoute->notify($notification);

                $this->alerts->recordNotification();
            }
        }
    }

    protected function resolveJobName(JobFailed $event): string
    {
        if (method_exists($event->job, 'resolveName')) {
            return $event->job->resolveName();
        }

        return $event->job?->getName() ?? 'Unknown Job';
    }
}


