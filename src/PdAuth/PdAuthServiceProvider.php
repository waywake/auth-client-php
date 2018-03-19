<?php

namespace PdAuth;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use PdAuth\Middleware\Authenticate;

class PdAuthServiceProvider extends ServiceProvider
{

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function (Request $request) {

            $token = $request->cookie(Authenticate::CookieName);

            if ($token) {
                try {
                    $user = app('pd.auth')->getUserInfo($token);
                    if ($user) {
                        return $user;
                    }
                } catch (DecryptException $ex) {
                    return null;
                }
            }
            return null;
        });
    }

    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/../config.php');

        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('pdauth.php')], 'pdauth');
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('pdauth');
        }

        $this->mergeConfigFrom($source, 'pdauth');
    }

    public function register()
    {
        $this->app->singleton('pd.auth', function () {
            $this->app->configure('pdauth');
            return new OAuth(config('pdauth'));
        });
    }

}