<?php

namespace App\Modules\IPTV\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\IPTV\Models\IPTV;
use App\Modules\IPTV\Policies\IPTVPolicy;
use App\Modules\IPTV\Repositories\Contracts\IPTVRepositoryInterface;
use App\Modules\IPTV\Repositories\IPTVRepository;

class IPTVServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            IPTVRepositoryInterface::class,
            IPTVRepository::class
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
        Gate::policy(IPTV::class, IPTVPolicy::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // $this->commands([]);
        }
    }

    protected function registerObservers(): void
    {
        // IPTV::observe(IPTVObserver::class);
    }
}
