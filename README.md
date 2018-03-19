# Auth 系统 PHP Client


> 该项目使用 composer 来完成加载



执行 
```bash
composer config repositories.php-auth-client vcs git@git.int.haowumc.com:arch/php-auth-client.git
composer require arch/php-auth-client
```


### 代码中启用

* 注册中间件

```php
$app->routeMiddleware([
    'auth' => PdAuth\Middleware\Authenticate::class,
]);
```

* 注册服务

```php
$app->register(PdAuth\PdAuthServiceProvider::class);
```

### 配置

在项目 .env 文件中增加如下配置

```
PDAUTH_APP_ID=appid
PDAUTH_SECRET=123456
PDAUTH_HOST=http://auth.dev.haowumc.com
```