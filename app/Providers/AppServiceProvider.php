<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Tenancy\TenantManager::class, function ($app) {
            return new \App\Services\Tenancy\TenantManager();
        });

        $this->app->singleton(\App\Services\ThemeService::class, function ($app) {
            return new \App\Services\ThemeService($app->make(\App\Services\Tenancy\TenantManager::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
