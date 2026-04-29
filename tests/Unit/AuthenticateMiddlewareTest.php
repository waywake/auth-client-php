<?php

namespace Tests\Unit;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PdAuth\Middleware\Authenticate;
use Tests\Support\AuthFactoryFake;
use Tests\Support\PdAuthFake;
use Tests\TestCase;

class AuthenticateMiddlewareTest extends TestCase
{
    public function testJsonGuestResponseContainsLoginUrlFromRedirectInput(): void
    {
        $middleware = new Authenticate(new AuthFactoryFake(true));
        $this->app['config']->set('pdauth.code.unauthorized', 400499);
        $this->app->instance('pd.auth', new PdAuthFake());
        $request = Request::create('/private', 'GET', ['redirect' => 'https://front.test/callback'], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $middleware->handle($request, fn () => 'next', 'erp');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame([
            'code' => 400499,
            'msg' => 'Unauthorized',
            'data' => [
                'url' => 'connect:https://front.test/callback',
            ],
        ], $response->getData(true));
    }

    public function testJsonGuestResponseFallsBackToRefererHeader(): void
    {
        $middleware = new Authenticate(new AuthFactoryFake(true));
        $this->app->instance('pd.auth', new PdAuthFake());
        $request = Request::create('/private', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_REFERER' => 'https://front.test/current',
        ]);

        $response = $middleware->handle($request, fn () => 'next');

        $this->assertSame('connect:https://front.test/current', $response->getData(true)['data']['url']);
    }

    public function testHtmlGuestResponseRedirectsToDefaultTokenCallback(): void
    {
        $middleware = new Authenticate(new AuthFactoryFake(true));
        $this->app->instance('pd.auth', new PdAuthFake());
        $request = Request::create('https://app.test/private', 'GET');

        $response = $middleware->handle($request, fn () => 'next');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('connect:https://app.test/api/auth/token.html', $response->headers->get('Location'));
    }

    public function testAuthenticatedRequestContinuesPipeline(): void
    {
        $middleware = new Authenticate(new AuthFactoryFake(false));
        $request = Request::create('/private', 'GET');

        $this->assertSame('next-response', $middleware->handle($request, fn () => 'next-response'));
    }
}
