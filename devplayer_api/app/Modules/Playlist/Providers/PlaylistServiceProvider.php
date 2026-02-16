<?php

namespace App\Modules\Playlist\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Playlist\Models\Playlist;
use App\Modules\Playlist\Policies\PlaylistPolicy;
use App\Modules\Playlist\Repositories\Contracts\PlaylistRepositoryInterface;
use App\Modules\Playlist\Repositories\PlaylistRepository;

class PlaylistServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            PlaylistRepositoryInterface::class,
            PlaylistRepository::class
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
        Gate::policy(Playlist::class, PlaylistPolicy::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // $this->commands([]);
        }
    }

    protected function registerObservers(): void
    {
        // Playlist::observe(PlaylistObserver::class);
    }
}
