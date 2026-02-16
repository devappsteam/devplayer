<?php

namespace App\Modules\Stream\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Stream\Models\Stream;
use App\Modules\Stream\Policies\StreamPolicy;
use App\Modules\Stream\Repositories\Contracts\StreamRepositoryInterface;
use App\Modules\Stream\Repositories\StreamRepository;

class StreamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            StreamRepositoryInterface::class,
            StreamRepository::class
        );
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerCommands();
        $this->registerObservers();
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Stream::class, StreamPolicy::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // $this->commands([]);
        }
    }

    protected function registerObservers(): void
    {
        \App\Modules\Stream\Models\Stream::observe(\App\Modules\Stream\Observers\StreamObserver::class);
    }
}
