<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{

    protected string $moduleNamespace = 'App\\Modules';
    protected string $modulesPath = 'app/Modules';

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerModules();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->bootModules();
    }

    protected function registerModules(): void
    {
        $modulesPath = base_path($this->modulesPath);

        if (!File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $this->registerModule($moduleName);
        }
    }

    protected function registerModule(string $moduleName): void
    {
        $providerClass = "{$this->moduleNamespace}\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";

        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
        $configPath = base_path("{$this->modulesPath}/{$moduleName}/Config");

        if (File::exists($configPath)) {
            $configFiles = File::files($configPath);

            foreach ($configFiles as $configFile) {
                $configName = strtolower($moduleName) . '.' . $configFile->getFilenameWithoutExtension();
                $this->mergeConfigFrom($configFile->getPathname(), $configName);
            }
        }
    }

    protected function bootModules(): void
    {
        $modulesPath = base_path($this->modulesPath);

        if (!File::exists($modulesPath)) {
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $this->bootModule($moduleName);
        }
    }

    protected function bootModule(string $moduleName): void
    {

        $this->loadModuleRoutes($moduleName);
        $this->loadModuleMigrations($moduleName);
        $this->publishModuleConfig($moduleName);
    }

    protected function loadModuleRoutes(string $moduleName): void
    {
        $routesPath = base_path("{$this->modulesPath}/{$moduleName}/Routes");

        if (!File::exists($routesPath)) {
            return;
        }

        // Load API routes
        $apiRoutesFile = "{$routesPath}/api.php";

        if (File::exists($apiRoutesFile)) {
            Route::prefix('api/v1')
                ->middleware('api')
                ->group(function () use ($apiRoutesFile) {
                    require $apiRoutesFile;
                });
        }
    }

    protected function loadModuleMigrations(string $moduleName): void
    {
        $migrationsPath = base_path("{$this->modulesPath}/{$moduleName}/Database/Migrations");

        if (File::exists($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    protected function publishModuleConfig(string $moduleName): void
    {
        $configPath = base_path("{$this->modulesPath}/{$moduleName}/Config");

        if (!File::exists($configPath)) {
            return;
        }

        $configFiles = File::files($configPath);

        foreach ($configFiles as $configFile) {
            $configName = strtolower($moduleName) . '-' . $configFile->getFilenameWithoutExtension();

            $this->publishes([
                $configFile->getPathname() => config_path("{$configName}.php"),
            ], "{$moduleName}-config");
        }
    }
}
