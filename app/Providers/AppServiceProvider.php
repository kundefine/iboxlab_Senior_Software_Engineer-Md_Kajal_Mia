<?php

namespace App\Providers;

use App\FlightProviders\ProviderBimanBangla;
use App\FlightProviders\ProviderNovoAir;
use App\FlightProviders\ProviderUSBangla;
use App\Services\FlightAggregatorService;
use App\Services\FlightDeduplicator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FlightAggregatorService::class, function ($app) {
            return new FlightAggregatorService([
                $app->make(ProviderUSBangla::class),
                $app->make(ProviderBimanBangla::class),
                $app->make(ProviderNovoAir::class),
            ],
            $app->make(FlightDeduplicator::class)
            );
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
