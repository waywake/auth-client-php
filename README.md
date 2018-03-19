# Auth 系统 PHP Client


> 该项目使用 composer 来完成加载，需要对项目的 ``` composer.json ``` 中增加如下配置手动制定仓库的地址（因为仓库为公司内部私有的，不对外开放）


```json
  
  "repositories": [
    {
      "type": "vcs",
      "url": "git@git.int.haowumc.com:arch/php-auth-client.git"
    }
  ]

```

执行 
```bash
composer require arch/php-auth-client
```


### 代码中启用

注册中间件
```PHP
$app->routeMiddleware([
    'auth' => PdAuth\Middleware\Authenticate::class,
]);
```
注册
```PHP
$app->register(PdAuth\PdAuthServiceProvider::class);
```

### 配置

在项目 .env 文件中增加如下配置

```
PDAUTH_APP_ID=appid
PDAUTH_SECRET=123456
PDAUTH_HOST=http://auth.dev.haowumc.com
```