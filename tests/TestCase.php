<?php

namespace Tests;

use BadMethodCallException;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootstrapApplication();
        $this->setEnv('APP_ENV', 'local');
        $this->setEnv('APP_NAME', 'erp');
    }

    protected function tearDown(): void
    {
        $this->unsetEnv('APP_ENV');
        $this->unsetEnv('APP_NAME');
        $this->unsetEnv('RPC_AUTH_URI');
        unset($_COOKIE[\PdAuth\Middleware\Authenticate::CookieName]);
        Container::setInstance(null);
        parent::tearDown();
    }

    protected function bootstrapApplication(array $config = []): Application
    {
        $this->app = new Application(dirname(__DIR__));
        $this->app->instance('config', new Repository(array_replace_recursive([
            'app' => [
                'debug' => false,
            ],
            'auth' => [
                'guards' => [],
            ],
            'pdauth' => require dirname(__DIR__) . '/config/auth.php',
        ], $config)));
        $this->app->instance(ResponseFactoryContract::class, new SimpleResponseFactory());
        $this->app->instance('redirect', new SimpleRedirector());
        Container::setInstance($this->app);

        return $this->app;
    }

    protected function setEnv(string $key, string $value): void
    {
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    protected function unsetEnv(string $key): void
    {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    protected function requestWithRoute(string|array|null $uses, array $user = []): Request
    {
        $request = Request::create('/resource', 'GET');
        $request->setRouteResolver(fn () => is_array($uses) ? $uses : [null, ['uses' => $uses]]);
        $request->setUserResolver(fn () => $user ?: null);

        return $request;
    }
}

class SimpleResponseFactory implements ResponseFactoryContract
{
    public function make($content = '', $status = 200, array $headers = [])
    {
        return new Response($content, $status, $headers);
    }

    public function noContent($status = 204, array $headers = [])
    {
        return new Response('', $status, $headers);
    }

    public function view($view, $data = [], $status = 200, array $headers = [])
    {
        return $this->make('', $status, $headers);
    }

    public function json($data = [], $status = 200, array $headers = [], $options = 0)
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public function jsonp($callback, $data = [], $status = 200, array $headers = [], $options = 0)
    {
        return $this->json($data, $status, $headers, $options)->setCallback($callback);
    }

    public function stream($callback, $status = 200, array $headers = [])
    {
        throw new BadMethodCallException('Streams are not supported by the test response factory.');
    }

    public function streamJson($data, $status = 200, $headers = [], $encodingOptions = 15)
    {
        throw new BadMethodCallException('Streamed JSON is not supported by the test response factory.');
    }

    public function streamDownload($callback, $name = null, array $headers = [], $disposition = 'attachment')
    {
        throw new BadMethodCallException('Downloads are not supported by the test response factory.');
    }

    public function download($file, $name = null, array $headers = [], $disposition = 'attachment')
    {
        throw new BadMethodCallException('Downloads are not supported by the test response factory.');
    }

    public function file($file, array $headers = [])
    {
        throw new BadMethodCallException('Files are not supported by the test response factory.');
    }

    public function redirectTo($path, $status = 302, $headers = [], $secure = null)
    {
        return new RedirectResponse($path, $status, $headers);
    }

    public function redirectToRoute($route, $parameters = [], $status = 302, $headers = [])
    {
        return $this->redirectTo((string) $route, $status, $headers);
    }

    public function redirectToAction($action, $parameters = [], $status = 302, $headers = [])
    {
        return $this->redirectTo(is_array($action) ? implode('@', $action) : $action, $status, $headers);
    }

    public function redirectGuest($path, $status = 302, $headers = [], $secure = null)
    {
        return $this->redirectTo($path, $status, $headers);
    }

    public function redirectToIntended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        return $this->redirectTo($default, $status, $headers);
    }
}

class SimpleRedirector
{
    public function to($path, $status = 302, $headers = [], $secure = null): RedirectResponse
    {
        return new RedirectResponse($path, $status, $headers);
    }
}
