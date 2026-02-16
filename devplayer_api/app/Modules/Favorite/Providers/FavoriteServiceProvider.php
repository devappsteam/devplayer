<?php

namespace App\Modules\Favorite\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Favorite\Models\Favorite;
use App\Modules\Favorite\Policies\FavoritePolicy;
use App\Modules\Favorite\Repositories\Contracts\FavoriteRepositoryInterface;
use App\Modules\Favorite\Repositories\FavoriteRepository;

class FavoriteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            FavoriteRepositoryInterface::class,
            FavoriteRepository::class
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
        Gate::policy(Favorite::class, FavoritePolicy::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // $this->commands([]);
        }
    }

    protected function registerObservers(): void
    {
        // Favorite::observe(FavoriteObserver::class);
    }
}
