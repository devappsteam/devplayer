<?php

namespace App\Modules\Channel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Channel\Models\Channel;
use App\Modules\Channel\Policies\ChannelPolicy;
use App\Modules\Channel\Repositories\Contracts\ChannelRepositoryInterface;
use App\Modules\Channel\Repositories\ChannelRepository;

class ChannelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            ChannelRepositoryInterface::class,
            ChannelRepository::class
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
        Gate::policy(Channel::class, ChannelPolicy::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // $this->commands([]);
        }
    }

    protected function registerObservers(): void
    {
        \App\Modules\Channel\Models\Channel::observe(\App\Modules\Channel\Observers\ChannelObserver::class);
    }
}
