<?php

namespace Luminee\Esun;

use Illuminate\Support\ServiceProvider;

class EsunServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $config = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([$this->config . 'esun.php' => config_path('esun.php')]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (file_exists($this->config . 'esun.php')) $this->mergeConfigFrom($this->config . 'esun.php', 'esun');

        $this->app->singleton('es', function ($app) {
            return new Esun();
        });
    }

}