<?php

namespace Luminee\Esun;

use Illuminate\Support\ServiceProvider;

class EsunServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([realpath(__DIR__ . '/../config/esun.php') => config_path('esun.php')]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('es', function ($app) {
            return new Esun();
        });
        $this->mergeConfigFrom(realpath(__DIR__ . '/../config/esun.php'), 'esun');
    }
}