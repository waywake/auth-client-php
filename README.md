# Auth 系统 PHP Client


> 该项目使用 composer 来完成加载






### 配置步骤

1. 找侯小贝配置登录auth信息 ***AppId*** ***AppSecret*** ***guard***
2. 执行
 ```bash
 composer config repositories.php-auth-client vcs git@git.int.haowumc.com:composer/php-auth-client.git
 composer require paidian/php-auth-client
 ```
 
### 服务端代码使用

1. 删除路由验证用户登录信息中间件,如无请忽略。
2. 删除代码中验证权限代码 `$this->middleware(CheckRole::class);` 如无请忽略。
3. 获取登录者信息由 `$this->user = app('request')->user('auth');` 变更为 `$this->user`
4. 给前端提供获取当前登录者信息接口, 如有请忽略
5. 在需要验证登录信息的控制器中配置如下代码（ERP项目为例）
 ```php
     use \PdAuth\Controller;
 
     public function __construct()
     {
         //这里配置的是ERP对应的guard
         $guard = "erp";
         $this->auth($guard);
     }
 ```
####获取当前登录用户信息
 ```php
  $this->user 
 ```
 
#####上线需配置ENV 上线操作者配置
 ```env
 RPC_AUTH_URI=http://auth.in.haowumc.com
 AUTH_ERP_SECRET=123456
 ```
 
###前端代码使用
1. 获取登录者信息 未登录 http code 返回 401和登录地址 ___客户端需重新定义redirect地址___
2. 用户扫码返回 ***pd_code*** ***app_id***
3. 获取token地址 `api/auth/token.json` 请求方式:get 参数: ***pd_code*** ***app_id***
4. 获取退出登录地址 `/api/auth/logout` 请求方式:get 参数:无