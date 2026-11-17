<?php

namespace App\Providers;

use App\Auth\CustomUserProvider;
use App\Models\Company;
use App\Observers\CompanyObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('custom', function ($app, array $config) {
            return new CustomUserProvider($app['hash'], $config['model']);
        });

        // Creates a company's subdomain in cPanel when it is created or its custom URL changes
        // (does nothing unless config/cpanel.php is switched on).
        Company::observe(CompanyObserver::class);
    }
}
