<?php

namespace Tests\Unit;

use JsonRpc\Client;
use PdAuth\Auth;
use PdAuth\Middleware\Authenticate;
use ReflectionClass;
use Tests\Support\RpcClientFake;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function testChooseBuildsLocalConnectUrlForConfiguredApplication(): void
    {
        $this->app->instance('rpc.auth', new RpcClientFake());

        $auth = new Auth($this->config());

        $this->assertSame(
            'http://auth.dev.haowumc.com/connect?appid=100009&redirect=https%3A%2F%2Fapp.test%2Fcallback',
            $auth->choose('erp-api')->connect('https://app.test/callback')
        );
    }

    public function testChooseCanResolveApplicationById(): void
    {
        $rpc = new RpcClientFake([
            'oauth.access_token' => ['access_token' => 'token'],
        ]);
        $this->app->instance('rpc.auth', $rpc);

        $token = (new Auth($this->config()))->choose(null, '100010')->getAccessToken('code-1');

        $this->assertSame(['access_token' => 'token'], $token);
        $this->assertSame([
            ['oauth.access_token', ['100010', 'crm-secret', 'code-1']],
        ], $rpc->calls);
    }

    public function testRpcMethodsUseSelectedApplicationCredentials(): void
    {
        $rpc = new RpcClientFake([
            'oauth.access_token' => ['access_token' => 'token'],
            'oauth.user_info' => ['id' => 10],
            'oauth.logout' => ['ok' => true],
        ]);
        $this->app->instance('rpc.auth', $rpc);

        $auth = (new Auth($this->config()))->choose('finance');

        $this->assertSame(['access_token' => 'token'], $auth->getAccessToken('code-2'));
        $this->assertSame(['id' => 10], $auth->getUserInfo('token-1'));
        $this->assertSame(['ok' => true], $auth->logout('token-1'));
        $this->assertSame([
            ['oauth.access_token', ['100003', 'finance-secret', 'code-2']],
            ['oauth.user_info', ['100003', 'finance-secret', 'token-1']],
            ['oauth.logout', ['100003', 'finance-secret', 'token-1']],
        ], $rpc->calls);
    }

    public function testProductionEnvironmentUsesProductionHost(): void
    {
        $this->setEnv('APP_ENV', 'production');
        $this->app->instance('rpc.auth', new RpcClientFake());

        $url = (new Auth($this->config()))->choose('op')->connect('/after-login');

        $this->assertSame('https://auth.int.haowumc.com/connect?appid=100006&redirect=%2Fafter-login', $url);
    }

    public function testUnknownEnvironmentThrowsException(): void
    {
        $this->setEnv('APP_ENV', 'staging');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('"APP_ENV" is not defined or not allow');

        new Auth($this->config());
    }

    public function testStandaloneFallbackInitializesJsonRpcClientEndpointForVersion21(): void
    {
        $this->setEnv('APP_NAME', 'auth-client-test');
        $this->setEnv('RPC_AUTH_URI', 'http://rpc-auth.test');

        $auth = new Auth($this->config());
        $rpc = $this->protectedValue($auth, 'rpc');

        $this->assertInstanceOf(Client::class, $rpc);
        $this->assertSame('http://rpc-auth.test', $this->protectedValue($rpc, 'server_config')['base_uri']);
        $this->assertNotNull($this->protectedValue($rpc, 'http'));
    }

    public function testChooseUsesDefaultAppNameAndCoversSupportedApplications(): void
    {
        $this->setEnv('APP_NAME', 'ds-api');
        $this->app->instance('rpc.auth', new RpcClientFake());
        $auth = new Auth($this->config());

        $this->assertSame('100011', $this->idAfterChoose($auth));
        $this->assertSame('100007', $this->idAfterChoose($auth, 'payment'));
        $this->assertSame('100007', $this->idAfterChoose($auth, 'paymeny_api'));
        $this->assertSame('100005', $this->idAfterChoose($auth, 'xiaoke'));
        $this->assertSame('100005', $this->idAfterChoose($auth, 'xiaoke_api'));
        $this->assertSame('100011', $this->idAfterChoose($auth, null, 'missing-app-id'));
    }

    public function testBindHwUserRejectsInvalidIdsBeforeCallingHttpLayer(): void
    {
        $this->app->instance('rpc.auth', new RpcClientFake());

        $auth = new Auth($this->config());

        $this->assertNull($auth->bindHwUser(0, 20));
        $this->assertNull($auth->bindHwUser(20, 0));
    }

    public function testGetGroupUsersUsesCookieTokenWhenTokenIsMissing(): void
    {
        $_COOKIE[Authenticate::CookieName] = 'cookie token';
        $this->app->instance('rpc.auth', new RpcClientFake());
        $auth = new HttpAuthStub($this->config());
        $auth->getResponse = [
            'code' => 0,
            'data' => [['id' => 1]],
        ];

        $this->assertSame([['id' => 1]], $auth->getGroupUsers());
        $this->assertSame('http://auth.dev.haowumc.com/api/group/users?access_token=cookie+token', $auth->lastGetUrl);
    }

    public function testGetGroupUsersReturnsNullForNonZeroResponseCode(): void
    {
        $this->app->instance('rpc.auth', new RpcClientFake());
        $auth = new HttpAuthStub($this->config());
        $auth->getResponse = [
            'code' => 1,
            'message' => 'failed',
        ];

        $this->assertNull($auth->getGroupUsers('bad-token'));
    }

    public function testBindHwUserPostsMappedPayload(): void
    {
        $this->app->instance('rpc.auth', new RpcClientFake());
        $auth = new HttpAuthStub($this->config());
        $auth->postResponse = ['code' => 0];

        $this->assertSame(['code' => 0], $auth->bindHwUser(10, 20));
        $this->assertSame('http://auth.dev.haowumc.com/api/bind/hwuser', $auth->lastPostUrl);
        $this->assertSame([
            'id' => 10,
            'hwmc_id' => 20,
        ], $auth->lastPostPayload);
    }

    private function config(): array
    {
        return [
            'apps' => [
                'op' => ['id' => '100006', 'secret' => 'op-secret'],
                'erp' => ['id' => '100009', 'secret' => 'erp-secret'],
                'crm' => ['id' => '100010', 'secret' => 'crm-secret'],
                'ds' => ['id' => '100011', 'secret' => 'ds-secret'],
                'payment' => ['id' => '100007', 'secret' => 'payment-secret'],
                'xiaoke' => ['id' => '100005', 'secret' => 'xiaoke-secret'],
                'finance' => ['id' => '100003', 'secret' => 'finance-secret'],
            ],
        ];
    }

    private function protectedValue(object $object, string $property): mixed
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);

        return $property->getValue($object);
    }

    private function idAfterChoose(Auth $auth, ?string $name = null, ?string $id = null): string
    {
        $auth->choose($name, $id);

        return $this->protectedValue($auth, 'id');
    }
}

class HttpAuthStub extends Auth
{
    public array $getResponse = ['code' => 1];

    public array $postResponse = [];

    public string|null $lastGetUrl = null;

    public string|null $lastPostUrl = null;

    public array|null $lastPostPayload = null;

    protected function get(string $url): array
    {
        $this->lastGetUrl = $url;

        return $this->getResponse;
    }

    protected function post(string $url, array $payload): array
    {
        $this->lastPostUrl = $url;
        $this->lastPostPayload = $payload;

        return $this->postResponse;
    }
}
