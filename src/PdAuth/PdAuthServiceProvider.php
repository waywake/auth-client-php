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

        $this->app['auth']->viaRequest('auth', function (Request $request) {

            $token = $request->header('Authorization', $request->cookie(Authenticate::CookieName));

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

//
        $config = $this->app['config']['auth'];

        if (!isset($config['guards']['auth'])) {
            config(['auth.guards.auth' => ['driver' => 'auth']]);
            $this->app['auth']->shouldUse('auth');
        }
    }

    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/../config/pdauth.php');

        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('pdauth.php')], 'pdauth');
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('pdauth');
        }

        $this->mergeConfigFrom($source, 'pdauth');
    }

    public function register()
    {
        $this->setupConfig();
        $this->app->singleton('pd.auth', function () {
            return new OAuth(config('pdauth'));
        });
    }

}