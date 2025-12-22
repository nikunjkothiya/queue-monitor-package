<?php

namespace NikunjKothiya\QueueMonitor\Providers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use NikunjKothiya\QueueMonitor\Commands\ComputeAnalyticsCommand;
use NikunjKothiya\QueueMonitor\Commands\InstallCommand;
use NikunjKothiya\QueueMonitor\Commands\PruneFailuresCommand;
use NikunjKothiya\QueueMonitor\Http\Middleware\AuthorizeQueueMonitor;
use NikunjKothiya\QueueMonitor\Listeners\JobFailedListener;
use NikunjKothiya\QueueMonitor\Listeners\JobProcessedListener;

class QueueMonitorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/queue-monitor.php', 'queue-monitor');
    }

    public function boot(): void
    {
        if (! config('queue-monitor.enabled')) {
            return;
        }

        $this->registerRoutes();
        $this->registerViews();
        $this->registerMigrations();
        $this->registerCommands();
        $this->registerListeners();
        $this->registerMiddleware();

        $this->publishes([
            __DIR__ . '/../../config/queue-monitor.php' => config_path('queue-monitor.php'),
        ], 'queue-monitor-config');

        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/queue-monitor'),
        ], 'queue-monitor-views');

        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'queue-monitor-migrations');
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'queue-monitor');
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneFailuresCommand::class,
                ComputeAnalyticsCommand::class,
                InstallCommand::class,
            ]);
        }
    }

    protected function registerListeners(): void
    {
        Event::listen(JobFailed::class, [JobFailedListener::class, 'handle']);
        Event::listen(JobProcessed::class, [JobProcessedListener::class, 'handle']);
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // Register a route middleware alias used only by this package's routes.
        $router->aliasMiddleware('queue-monitor', AuthorizeQueueMonitor::class);
    }
}


