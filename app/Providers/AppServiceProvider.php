<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Rating;
use App\Observers\OrderObserver;
use App\Observers\RatingObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Order::observe(OrderObserver::class);
        Rating::observe(RatingObserver::class);
    }
}
