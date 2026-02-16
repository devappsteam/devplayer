<?php

namespace App\Modules\Category\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Policies\CategoryPolicy;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use App\Modules\Category\Repositories\CategoryRepository;

class CategoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interface to implementation
        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class
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
        Gate::policy(Category::class, CategoryPolicy::class);
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // $this->commands([]);
        }
    }

    protected function registerObservers(): void
    {
        \App\Modules\Category\Models\Category::observe(\App\Modules\Category\Observers\CategoryObserver::class);
    }
}
