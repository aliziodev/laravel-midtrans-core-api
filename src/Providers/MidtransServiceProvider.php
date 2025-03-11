<?php

namespace Aliziodev\LaravelMidtrans\Providers;

use Illuminate\Support\ServiceProvider;
use Aliziodev\LaravelMidtrans\Services\CoreApi;

class MidtransServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/midtrans.php', 'midtrans'
        );

        $this->registerServices();
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Config/midtrans.php' => $this->app->configPath('midtrans.php'),
            ], 'midtrans-config');
        }
    }

    protected function registerServices()
    {
        $this->app->singleton('midtrans', function ($app) {
            return new CoreApi();
        });

        $this->app->alias('midtrans', CoreApi::class);
    }
}