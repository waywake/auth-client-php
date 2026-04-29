# Waywake Auth Client

`waywake/auth-client` 是公司内部 Auth 系统的 PHP 客户端包，面向 Laravel/Lumen 12 应用。它通过 `waywake/json-rpc` 调用 Auth 服务，提供：

- OAuth code 换 access token
- 根据 token 获取当前用户信息
- 登录中间件和权限中间件
- Web 登录跳转 URL 生成
- Laravel/Lumen 服务提供者和内置 token/logout 路由

## 环境要求

- PHP `^8.4`
- Laravel/Lumen 12 相关组件
- `waywake/json-rpc ^2.1`

开发测试使用 PHPUnit 11，覆盖率使用 PCOV。

## 安装

```bash
composer require waywake/auth-client
```

如果项目没有自动加载服务提供者，需要手动注册：

```php
PdAuth\PdAuthServiceProvider::class
```

Laravel 项目可发布配置：

```bash
php artisan vendor:publish --tag=pdauth
```

Lumen 项目在应用启动时注册服务提供者即可，包会调用 `configure('pdauth')`。

## 配置

配置文件来源是 `config/auth.php`，合并到应用配置键 `pdauth`。

默认支持的应用：

- `op`
- `erp`
- `crm`
- `ds`
- `payment`
- `xiaoke`
- `finance`

常用环境变量：

```env
APP_ENV=local
APP_NAME=erp
RPC_AUTH_URI=http://auth.dev.haowumc.com

AUTH_OP_SECRET=123456
AUTH_ERP_SECRET=123456
AUTH_CRM_SECRET=123456
AUTH_DS_SECRET=123456
AUTH_PAYMENT_SECRET=123456
AUTH_XIAOKE_SECRET=123456
AUTH_FINANCE_SECRET=123456
```

`APP_ENV` 会影响 Auth Web 地址：

- `local` / `develop`: `http://auth.dev.haowumc.com`
- `production`: `https://auth.int.haowumc.com`

`RPC_AUTH_URI` 用于 JSON-RPC 客户端访问 Auth 服务。若应用容器里已经绑定 `rpc.auth`，本包会优先复用该客户端；否则会创建 `JsonRpc\Client` 并选择 `auth` endpoint。

## 控制器用法

控制器中引入 `PdAuth\Controller` trait，并在构造函数中选择 guard：

```php
use PdAuth\Controller;

class UserController
{
    use Controller;

    public function __construct()
    {
        $this->auth('erp');
    }

    public function me()
    {
        return $this->user;
    }
}
```

`auth($guard)` 会完成：

- `app('auth')->shouldUse($guard)`
- `app('pd.auth')->choose($guard)`
- 注册 `PdAuth\Middleware\Authenticate`
- 注册 `PdAuth\Middleware\CheckRole`
- 将当前用户写入 `$this->user`

## 权限配置

`CheckRole` 会读取当前路由 action 对应控制器上的 `Privileges`。支持常量或静态属性：

```php
class OrderController
{
    public const Privileges = [
        'index' => ['admin', 'sales'],
        'show' => '*',
    ];
}
```

规则：

- action 配置为 `'*'` 时直接放行
- 用户没有 `roles` 时返回 403
- 用户角色和 action 角色无交集时返回 403
- 控制器或 action 未定义权限时返回 403

## 前端登录流程

未登录访问受保护接口时：

- JSON 请求返回 HTTP `401`
- 响应体包含 `data.url`，前端应跳转到该地址登录

示例响应：

```json
{
  "code": 400401,
  "msg": "Unauthorized",
  "data": {
    "url": "http://auth.dev.haowumc.com/connect?appid=100009&redirect=..."
  }
}
```

扫码登录后 Auth 会返回 `pd_code` 和 `app_id`，前端调用：

```text
GET /api/auth/token.json?pd_code=...&app_id=...
```

成功后返回 token 数据，并写入 `token` cookie。

退出登录：

```text
GET /api/auth/logout
```

响应中的 `data.url` 是 Auth 系统退出地址，客户端可继续跳转。

## Auth API

```php
$auth = app('pd.auth')->choose('erp');

$loginUrl = $auth->connect('https://app.example.com/callback');
$token = $auth->getAccessToken($code);
$user = $auth->getUserInfo($token['access_token']);
$auth->logout($token['access_token']);
```

也可以按 `app_id` 选择应用：

```php
$auth = app('pd.auth')->choose(null, '100009');
```

## 路由

服务提供者会注册：

```text
GET api/auth/token.json
GET api/auth/token.html
GET api/auth/logout
```

`token.json` 返回 JSON，`token.html` 写 cookie 后重定向到 `/`。

## 测试

安装依赖：

```bash
composer install
```

运行测试：

```bash
composer test
```

使用 PCOV 生成覆盖率：

```bash
composer test:coverage
```

当前测试覆盖核心类：

- `PdAuth\Auth`
- `PdAuth\Controller`
- `PdAuth\PdAuthServiceProvider`
- `PdAuth\Middleware\Authenticate`
- `PdAuth\Middleware\CheckRole`

`tests/1.php` 是历史手动调试脚本，不属于 PHPUnit 测试套件。
