<?php

namespace NikunjKothiya\QueueMonitor\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    protected $signature = 'queue-monitor:install {--force : Overwrite existing files when publishing}';

    protected $description = 'Install Laravel Queue Monitor (publish config, migrations, views, and run migrations)';

    public function handle(): int
    {
        $this->info('Publishing Queue Monitor configuration...');
        Artisan::call('vendor:publish', [
            '--provider' => 'NikunjKothiya\\QueueMonitor\\Providers\\QueueMonitorServiceProvider',
            '--tag' => 'queue-monitor-config',
            '--force' => $this->option('force'),
        ]);
        $this->output->write(Artisan::output());

        $this->info('Publishing Queue Monitor migrations...');
        Artisan::call('vendor:publish', [
            '--provider' => 'NikunjKothiya\\QueueMonitor\\Providers\\QueueMonitorServiceProvider',
            '--tag' => 'queue-monitor-migrations',
            '--force' => $this->option('force'),
        ]);
        $this->output->write(Artisan::output());

        $this->info('Publishing Queue Monitor views...');
        Artisan::call('vendor:publish', [
            '--provider' => 'NikunjKothiya\\QueueMonitor\\Providers\\QueueMonitorServiceProvider',
            '--tag' => 'queue-monitor-views',
            '--force' => $this->option('force'),
        ]);
        $this->output->write(Artisan::output());

        $this->info('Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->output->write(Artisan::output());

        $this->info('Queue Monitor installation completed.');

        return self::SUCCESS;
    }
}


