<?php

namespace App\Providers;

use App\Events\IngredientStockLow;
use App\Listeners\SendStockAlertEmail;
use Illuminate\Support\Facades\Event;
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
        // No EventServiceProvide in laravel 11 by default (if auto-discover not works)
        Event::listen(
            IngredientStockLow::class,
            SendStockAlertEmail::class,
        );
    }
}
