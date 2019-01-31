# Auth 系统 PHP Client


> 该项目使用 composer 来完成加载



执行 
```bash
composer config repositories.php-auth-client vcs git@git.int.haowumc.com:composer/php-auth-client.git
composer require paidian/php-auth-client
```


### 代码中启用

* 注册服务

```php
$app->register(PdAuth\PdAuthServiceProvider::class);
```
