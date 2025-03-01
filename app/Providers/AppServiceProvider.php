<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // HEAD logic: Force HTTP scheme in dev environment
        if (env('APP_ENV') !== 'production') {
            URL::forceScheme('http');
        }

        // Morph map for polymorphic relations
        Relation::morphMap([
            'sales' => 'App\Models\Sale',
            'price_requests' => 'App\Models\PriceRequest',
            // Add other models as needed
        ]);
    }
}
