<?php

namespace Tests\Unit;

use PdAuth\Middleware\CheckRole;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CheckRoleMiddlewareTest extends TestCase
{
    public function testAllowsWildcardPrivilege(): void
    {
        $request = $this->requestWithRoute(RoleController::class . '@open');

        $this->assertSame('allowed', (new CheckRole())->handle($request, fn () => 'allowed'));
    }

    public function testAllowsUserWithIntersectingRole(): void
    {
        $request = $this->requestWithRoute(RoleController::class . '@index', [
            'roles' => ['sales', 'admin'],
        ]);

        $this->assertSame('allowed', (new CheckRole())->handle($request, fn () => 'allowed'));
    }

    public function testAllowsInvokableControllerPrivilege(): void
    {
        $request = $this->requestWithRoute(RoleController::class, [
            'roles' => ['runner'],
        ]);

        $this->assertSame('allowed', (new CheckRole())->handle($request, fn () => 'allowed'));
    }

    public function testAllowsStaticPropertyPrivilegeDefinitions(): void
    {
        $request = $this->requestWithRoute(StaticPropertyPrivilegeController::class . '@index');

        $this->assertSame('allowed', (new CheckRole())->handle($request, fn () => 'allowed'));
    }

    public function testReadsActionNameFromRouteObject(): void
    {
        $request = $this->requestWithRoute([null, ['uses' => null]], [
            'roles' => ['admin'],
        ]);
        $request->setRouteResolver(fn () => new RouteWithActionName(RoleController::class . '@index'));

        $this->assertSame('allowed', (new CheckRole())->handle($request, fn () => 'allowed'));
    }

    public function testReadsUsesValueFromRouteActionArray(): void
    {
        $request = $this->requestWithRoute([null, ['uses' => null]]);
        $request->setRouteResolver(fn () => new RouteWithActionArray(RoleController::class . '@open'));

        $this->assertSame('allowed', (new CheckRole())->handle($request, fn () => 'allowed'));
    }

    public function testRejectsRoutesWithoutUsableAction(): void
    {
        $this->expectForbidden('未定义权限');

        (new CheckRole())->handle($this->requestWithRoute('Closure'), fn () => 'allowed');
    }

    public function testRejectsMissingControllerClass(): void
    {
        $this->expectForbidden('未定义权限');

        (new CheckRole())->handle($this->requestWithRoute('MissingController@index'), fn () => 'allowed');
    }

    public function testRejectsControllerWithoutPrivilegeDefinition(): void
    {
        $this->expectForbidden('未定义权限');

        (new CheckRole())->handle($this->requestWithRoute(NoPrivilegeController::class . '@index'), fn () => 'allowed');
    }

    public function testRejectsMissingActionPrivilege(): void
    {
        $this->expectForbidden('未定义权限');

        (new CheckRole())->handle($this->requestWithRoute(RoleController::class . '@missing'), fn () => 'allowed');
    }

    public function testRejectsUserWithoutRoles(): void
    {
        $this->expectForbidden('无权访问');

        (new CheckRole())->handle($this->requestWithRoute(RoleController::class . '@index', ['id' => 1]), fn () => 'allowed');
    }

    public function testRejectsUserWithoutMatchingRole(): void
    {
        $this->expectForbidden('无权访问，请联系管理员授权');

        (new CheckRole())->handle($this->requestWithRoute(RoleController::class . '@index', [
            'roles' => ['guest'],
        ]), fn () => 'allowed');
    }

    private function expectForbidden(string $message): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage($message);
    }
}

class RoleController
{
    public const Privileges = [
        'index' => ['admin'],
        'open' => '*',
        '__invoke' => ['runner'],
    ];
}

class NoPrivilegeController
{
}

class StaticPropertyPrivilegeController
{
    public static array $Privileges = [
        'index' => '*',
    ];
}

class RouteWithActionName
{
    public function __construct(private string $actionName)
    {
    }

    public function getActionName(): string
    {
        return $this->actionName;
    }
}

class RouteWithActionArray
{
    public function __construct(private string $uses)
    {
    }

    public function getAction(): array
    {
        return ['uses' => $this->uses];
    }
}
