<?php

namespace Tests\Unit;

use PdAuth\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PdAuth\Middleware\Authenticate;
use PdAuth\PdAuthServiceProvider;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\Support\AuthFactoryFake;
use Tests\Support\PdAuthFake;
use Tests\Support\RouterFake;
use Tests\TestCase;

class PdAuthServiceProviderTest extends TestCase
{
    public function testRegisterResolvesPdAuthSingleton(): void
    {
        $provider = new PdAuthServiceProvider($this->app);
        $this->app->instance('rpc.auth', new \Tests\Support\RpcClientFake());

        $provider->register();

        $this->assertInstanceOf(Auth::class, $this->app->make('pd.auth'));
    }

    public function testRegisterConfiguresLumenApplications(): void
    {
        $app = new \Laravel\Lumen\Application(dirname(__DIR__, 2));
        $app->instance('config', $this->app->make('config'));
        $app->instance('rpc.auth', new \Tests\Support\RpcClientFake());

        $provider = new PdAuthServiceProvider($app);
        $provider->register();

        $this->assertSame(['pdauth'], $app->configured);
        $this->assertSame('100006', $app['config']->get('pdauth.apps.op.id'));
    }

    public function testBootRegistersGuardsRequestCallbacksAndAuthRoutes(): void
    {
        $auth = new AuthFactoryFake();
        $router = new RouterFake();
        $pdAuth = new PdAuthFake(['id' => 9, 'roles' => ['admin']]);
        $provider = new PdAuthServiceProvider($this->app);

        $this->app->instance('auth', $auth);
        $this->app->instance('router', $router);

        $provider->register();
        $this->app->instance('pd.auth', $pdAuth);
        $provider->boot();

        $this->assertSame(['driver' => 'erp'], $this->app['config']->get('auth.guards.erp'));
        $this->assertArrayHasKey('erp', $auth->requestCallbacks);
        $this->assertSame([
            'api/auth/token.json',
            'api/auth/token.html',
            'api/auth/logout',
        ], array_keys($router->routes));

        $request = Request::create('/private', 'GET', [], [Authenticate::CookieName => 'cookie-token'], [], [
            'HTTP_AUTHORIZATION' => 'header-token',
        ]);

        $this->assertSame(['id' => 9, 'roles' => ['admin']], $auth->requestCallbacks['erp']($request));
        $this->assertSame([['erp', null]], $pdAuth->choices);
    }

    public function testViaRequestReturnsNullWhenTokenIsMissing(): void
    {
        $auth = new AuthFactoryFake();
        $provider = new PdAuthServiceProvider($this->app);
        $this->app->instance('auth', $auth);
        $this->app->instance('router', new RouterFake());

        $provider->register();
        $this->app->instance('pd.auth', new PdAuthFake());
        $provider->boot();

        $this->assertNull($auth->requestCallbacks['erp'](Request::create('/private', 'GET')));
    }

    public function testTokenJsonRouteReturnsTokenPayloadAndCookie(): void
    {
        [$router, $pdAuth] = $this->bootProviderWithFakes();

        $response = $router->routes['api/auth/token.json'](Request::create('/token', 'GET', [
            'pd_code' => 'code-1',
            'app_id' => '100009',
        ]));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame([
            'code' => 0,
            'message' => '',
            'data' => $pdAuth->token + ['code' => 'code-1'],
        ], $response->getData(true));
        $this->assertSame([[null, '100009']], $pdAuth->choices);
        $this->assertCookie($response->headers->getCookies()[0], Authenticate::CookieName, 'token-value');
    }

    public function testTokenHtmlRouteRedirectsHomeWithTokenCookie(): void
    {
        [$router] = $this->bootProviderWithFakes();

        $response = $router->routes['api/auth/token.html'](Request::create('/token', 'GET', [
            'pd_code' => 'code-2',
            'app_id' => '100010',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->headers->get('Location'));
        $this->assertCookie($response->headers->getCookies()[0], Authenticate::CookieName, 'token-value');
    }

    public function testLogoutRouteClearsCookieAndReturnsEnvironmentLogoutUrl(): void
    {
        [$router] = $this->bootProviderWithFakes();

        $response = $router->routes['api/auth/logout'](Request::create('/logout', 'GET'));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('http://auth.dev.haowumc.com/logout', $response->getData(true)['data']['url']);
        $this->assertCookie($response->headers->getCookies()[0], Authenticate::CookieName, '');
    }

    private function bootProviderWithFakes(): array
    {
        $auth = new AuthFactoryFake();
        $router = new RouterFake();
        $pdAuth = new PdAuthFake();
        $provider = new PdAuthServiceProvider($this->app);

        $this->app->instance('auth', $auth);
        $this->app->instance('router', $router);
        $provider->register();
        $this->app->instance('pd.auth', $pdAuth);
        $provider->boot();

        return [$router, $pdAuth, $auth];
    }

    private function assertCookie(Cookie $cookie, string $name, string $value): void
    {
        $this->assertSame($name, $cookie->getName());
        $this->assertSame($value, $cookie->getValue());
    }
}
