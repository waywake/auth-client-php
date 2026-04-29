<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use PdAuth\Middleware\Authenticate;
use PdAuth\Middleware\CheckRole;
use Tests\Support\AuthFactoryFake;
use Tests\Support\PdAuthFake;
use Tests\TestCase;

class ControllerTraitTest extends TestCase
{
    public function testAuthSelectsGuardRegistersMiddlewareAndStoresUser(): void
    {
        $auth = new AuthFactoryFake(false);
        $pdAuth = new PdAuthFake();
        $request = Request::create('/controller', 'GET');
        $request->setUserResolver(fn ($guard = null) => ['id' => 5, 'guard' => $guard]);

        $this->app->instance('auth', $auth);
        $this->app->instance('pd.auth', $pdAuth);
        $this->app->instance('request', $request);

        $controller = new ControllerUsingTrait();
        $controller->auth('erp');

        $this->assertSame('erp', $auth->usedGuard);
        $this->assertSame([['erp', null]], $pdAuth->choices);
        $this->assertSame([Authenticate::class, CheckRole::class], $controller->middleware);
        $this->assertSame('erp', $controller->guard());
        $this->assertSame(['id' => 5, 'guard' => 'erp'], $controller->user());
    }
}

class ControllerUsingTrait
{
    use \PdAuth\Controller;

    public array $middleware = [];

    public function middleware($middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function guard(): ?string
    {
        return $this->guard;
    }

    public function user(): mixed
    {
        return $this->user;
    }
}
