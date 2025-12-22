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
        $jobName = $this->resolveJobName($event);
        $jobClass = $jobName; // Usually same as class name in Laravel

        $exception = $event->exception;
        $exceptionClass = $exception::class;
        $file = $exception->getFile();
        $line = $exception->getLine();

        // Calculate a hash to group similar failures together
        $groupHash = md5(implode('|', [
            $jobClass,
            $exceptionClass,
            $file,
            $line,
        ]));

        $failure = QueueFailure::create([
            'connection' => $event->connectionName,
            'queue' => $event->job?->getQueue(),
            'job_name' => $jobName,
            'job_class' => $jobClass,
            'payload' => $payload,
            'exception_class' => $exceptionClass,
            'exception_message' => $exception->getMessage(),
            'file' => $file,
            'line' => $line,
            'stack_trace' => $exception->getTraceAsString(),
            'group_hash' => $groupHash,
            'hostname' => gethostname(),
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


