<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(database_path('migrations/tenant'));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            Config::set('school', Cache::rememberForever(
                'school.settings',
                fn (): array => Setting::pluck('settingValue', 'settingName')->toArray()
            ));
        } catch (\Throwable $e) {
        }

        // MoonshineUser code...
    }
}
