<?php

namespace Tests\Support;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Authenticatable;

class RpcClientFake
{
    public array $calls = [];

    public function __construct(private array $responses = [])
    {
    }

    public function call(string $name, array $arguments): mixed
    {
        $this->calls[] = [$name, $arguments];

        return $this->responses[$name] ?? null;
    }
}

class PdAuthFake
{
    public array $choices = [];

    public function __construct(
        public mixed $user = ['id' => 1, 'roles' => ['admin']],
        public array $token = ['access_token' => 'token-value', 'expired_at' => '2030-01-01 00:00:00'],
    ) {
    }

    public function choose($name = null, $id = null): self
    {
        $this->choices[] = [$name, $id];

        return $this;
    }

    public function getUserInfo($token): mixed
    {
        return $this->user;
    }

    public function getAccessToken($code): array
    {
        return $this->token + ['code' => $code];
    }

    public function connect($redirect): string
    {
        return 'connect:' . $redirect;
    }
}

class AuthFactoryFake implements AuthFactory
{
    public string|null $usedGuard = null;

    public array $requestCallbacks = [];

    public function __construct(private bool $guest = true)
    {
    }

    public function guard($name = null)
    {
        return new GuardFake($this->guest);
    }

    public function shouldUse($name)
    {
        $this->usedGuard = $name;
    }

    public function viaRequest($driver, callable $callback): void
    {
        $this->requestCallbacks[$driver] = $callback;
    }
}

class GuardFake
{
    public function __construct(private bool $guest)
    {
    }

    public function check(): bool
    {
        return !$this->guest;
    }

    public function guest(): bool
    {
        return $this->guest;
    }

    public function user(): mixed
    {
        return null;
    }

    public function id(): null
    {
        return null;
    }

    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function hasUser(): bool
    {
        return false;
    }

    public function setUser(Authenticatable $user): self
    {
        return $this;
    }
}

class RouterFake
{
    public array $routes = [];

    public function get(string $uri, callable $action): void
    {
        $this->routes[$uri] = $action;
    }
}
