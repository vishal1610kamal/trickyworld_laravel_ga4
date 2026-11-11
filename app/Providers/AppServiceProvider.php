<?php

namespace App\Providers;

use App\Analytics\Ga4;
use Illuminate\Cookie\Middleware\EncryptCookies;
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
        // Laravel encrypts every cookie it reads or writes. gtag's cookies and the consent cookie are set in the
        // browser in plain text, so Laravel must leave them alone. The names come from config, which (unlike
        // env() in bootstrap/app.php) still works after `php artisan config:cache`.
        EncryptCookies::except([...Ga4::cookieNames(), config('ga4.consent_cookie')]);
    }
}
