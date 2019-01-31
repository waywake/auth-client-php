<?php

namespace PdAuth;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use PdAuth\Middleware\Authenticate;
use Symfony\Component\HttpFoundation\Cookie;

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
        $config = $this->app['config']['auth'];

        foreach ($this->app['config']['pdauth']['apps'] as $key => $app) {
            $this->app['auth']->viaRequest($key, function (Request $request) use ($key) {
                $token = $request->header('Authorization', $request->cookie(Authenticate::CookieName));
                if ($token) {
                    return app('pd.auth')->choose($key)->getUserInfo($token);
                }
                return null;
            });

            if (!isset($config['guards']['auth'])) {
                config(['auth.guards.' . $key => ['driver' => $key]]);
                $this->app['auth']->shouldUse('auth');
            }
        }

//        $config = $this->app['config']['auth'];
//
//        if (!isset($config['guards']['auth'])) {
//            config(['auth.guards.auth' => ['driver' => 'auth']]);
//            $this->app['auth']->shouldUse('auth');
//        }

        $this->setupRouter();
    }

    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/../config/auth.php');

        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('pdauth.php')], 'pdauth');
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('pdauth');
        }

        $this->mergeConfigFrom($source, 'pdauth');
    }

    protected function setupRouter()
    {
        //添加获取token的路由
        $this->app['router']->get('auth/token.json', function (Request $request) {
            $code = $request->input('pd_code');
            $id = $request->input('app_id');
            $token = app('pd.auth')->choose(null, $id)->getAccessToken($code);
            $cookie = new Cookie(Authenticate::CookieName, $token['access_token'], strtotime($token['expired_at']));
            return response()->json([
                'code' => 0,
                'message' => '',
                'data' => $token,
            ])->withCookie($cookie);
        });

        $this->app['router']->get('auth/logout', function (Request $request) {
            app('pd.auth')->logout();
        });
    }

    public function register()
    {
        $this->setupConfig();
        $this->app->singleton('pd.auth', function () {
            return new Auth(config('pdauth'));
        });
    }

}