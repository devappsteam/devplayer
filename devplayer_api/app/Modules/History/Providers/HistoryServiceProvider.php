<?php

namespace App\Modules\History\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\History\Services\HistoryService;

class HistoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HistoryService::class, function () {
            return new HistoryService();
        });
    }

    public function boot(): void
    {
        //
    }
}
