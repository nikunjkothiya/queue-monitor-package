<?php

namespace NikunjKothiya\QueueMonitor\Services;

use Illuminate\Support\Arr;

class QueueDiagnosticsService
{
    public function summarize(): array
    {
        $default = config('queue.default');
        $connections = config('queue.connections', []);

        $status = 'ok';
        $messages = [];

        if (! $default) {
            $status = 'error';
            $messages[] = 'QUEUE_CONNECTION is not set.';
        } elseif (! Arr::has($connections, $default)) {
            $status = 'error';
            $messages[] = "Queue connection '{$default}' is not defined in config/queue.php.";
        } else {
            $messages[] = "Using '{$default}' queue connection.";
        }

        $driver = $connections[$default]['driver'] ?? null;

        $driverStatus = $this->driverDiagnostics($driver);

        if ($driverStatus['status'] === 'warning' && $status === 'ok') {
            $status = 'warning';
        } elseif ($driverStatus['status'] === 'error') {
            $status = 'error';
        }

        return [
            'default' => $default,
            'driver' => $driver,
            'status' => $status,
            'messages' => array_merge($messages, $driverStatus['messages']),
        ];
    }

    protected function driverDiagnostics(?string $driver): array
    {
        $messages = [];
        $status = 'ok';

        if (! $driver) {
            return [
                'status' => 'error',
                'messages' => ['Unable to determine queue driver for the default connection.'],
            ];
        }

        switch ($driver) {
            case 'redis':
                if (! config('database.redis.default.host')) {
                    $status = 'warning';
                    $messages[] = 'Redis connection is missing or incomplete. Check REDIS_* settings.';
                }
                break;

            case 'database':
                if (! config('queue.connections.database.table')) {
                    $status = 'warning';
                    $messages[] = 'Database queue table is not configured. Ensure QUEUE_TABLE and migrations exist.';
                }
                break;

            case 'sqs':
                if (! env('SQS_QUEUE')) {
                    $status = 'warning';
                    $messages[] = 'SQS_QUEUE is not set. Jobs may not be dispatched to the expected queue.';
                }
                break;

            case 'sync':
                $messages[] = 'Sync driver is for development only; jobs run immediately and are not queued.';
                break;
        }

        return compact('status', 'messages');
    }
}


