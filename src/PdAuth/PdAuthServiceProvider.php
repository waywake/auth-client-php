<?php

namespace PdAuth;

use Illuminate\Support\ServiceProvider;

class PdAuthServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('pd.auth', function () {
            $this->app->configure('pdauth');
            return new Client(config('pdauth'));
        });
    }

}