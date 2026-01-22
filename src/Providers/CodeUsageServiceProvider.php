<?php

namespace Qwerkon\CodeUsage\Providers;

use Qwerkon\CodeUsage\Console\CodeUsagePruneCommand;
use Qwerkon\CodeUsage\Console\CodeUsageReportCommand;
use Qwerkon\CodeUsage\Contracts\CodeUsageTracker;
use Qwerkon\CodeUsage\Jobs\FlushCodeUsageBatchJob;
use Qwerkon\CodeUsage\Middleware\TrackCodeUsageMiddleware;
use Qwerkon\CodeUsage\Services\DbBufferedCodeUsageTracker;
use Qwerkon\CodeUsage\Tracker\TrackingDispatcher;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;

class CodeUsageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/code_usage.php', 'code_usage');

        $this->app->singleton(CodeUsageTracker::class, function ($app) {
            return new DbBufferedCodeUsageTracker(
                $app['cache.store'],
                $app['log'],
                $app['config']['code_usage']
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->commands([
            CodeUsagePruneCommand::class,
            CodeUsageReportCommand::class,
        ]);

        if (! config('code_usage.enabled')) {
            return;
        }

        $this->app->singleton('events', function ($app) {
            return new TrackingDispatcher($app, $app->make(CodeUsageTracker::class));
        });

        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(TrackCodeUsageMiddleware::class);

        $this->app->terminating(fn () => $this->app->make(CodeUsageTracker::class)->flush());

        Event::listen(JobProcessing::class, function (JobProcessing $event) {
            $tracker = $this->app->make(CodeUsageTracker::class);
            $tracker->track(
                get_class($event->job),
                'job',
                ['queue' => $event->job->getQueue()]
            );
        });

        Event::listen(JobProcessed::class, function () {
            $this->app->make(CodeUsageTracker::class)->flush();
        });

        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            $this->app->make(CodeUsageTracker::class)->track(
                $event->command,
                'command',
                ['command' => $event->command]
            );
        });

        Event::listen(CommandFinished::class, function () {
            $this->app->make(CodeUsageTracker::class)->flush();
        });

    }
}
