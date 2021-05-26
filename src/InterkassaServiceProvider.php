<?php

namespace Gimmo\Interkassa;

use Illuminate\Support\ServiceProvider;

class InterkassaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/interkassa.php' => config_path('interkassa.php'),
        ], 'config');

        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/interkassa.php', 'interkassa');

        $this->app->singleton('interkassa', function () {
            return $this->app->make(Interkassa::class);
        });

        $this->app->alias('interkassa', 'Interkassa');
    }
}