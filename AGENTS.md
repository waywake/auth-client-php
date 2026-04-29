# AGENTS.md

本文件给后续维护这个仓库的编码 Agent 使用。开始改动前先读 `README.md`、`composer.json`、`src/` 和相关测试。

## 项目概览

这是 `waywake/auth-client`，一个 Laravel/Lumen 12 的 Auth 客户端包。

核心代码：

- `src/Auth.php`: Auth JSON-RPC 客户端封装，负责选择应用、生成登录 URL、换 token、查用户、退出、用户组和绑定好物用户。
- `src/PdAuthServiceProvider.php`: 合并配置、注册 `pd.auth` singleton、注册 guard 回调和内置 Auth 路由。
- `src/Controller.php`: 控制器 trait，统一注册登录和权限中间件，并写入 `$this->user`。
- `src/Middleware/Authenticate.php`: 未登录时返回 JSON 401 或跳转登录。
- `src/Middleware/CheckRole.php`: 根据控制器 `Privileges` 校验角色。
- `config/auth.php`: 默认 `pdauth` 配置。

测试代码：

- `tests/Unit/*Test.php`: PHPUnit 单元测试。
- `tests/Support/Fakes.php`: 测试 fake。
- `tests/Support/LumenApplication.php`: 仅用于覆盖 Lumen 分支的轻量 fake。
- `tests/1.php`: 历史手动调试脚本，不要纳入自动测试。

## 常用命令

```bash
composer install
composer test
composer test:coverage
find src config tests -name '*.php' -print0 | xargs -0 -n1 php -l
composer validate --no-check-publish
```

覆盖率命令依赖 PCOV：

```bash
php -d pcov.enabled=1 -d pcov.directory=src vendor/bin/phpunit --coverage-text
```

## 兼容性注意

- 当前包要求 PHP `^8.4`。
- 当前 JSON-RPC 依赖是 `waywake/json-rpc ^2.1`。
- `JsonRpc\Client` 2.1 需要配置 `app`，并且要通过 `endpoint('auth')` 初始化 endpoint 后再调用 `call()`。
- `Auth` 会优先使用容器中的 `app('rpc.auth')`。只有未绑定 `rpc.auth` 时才创建 fallback `JsonRpc\Client`。
- `APP_ENV` 只接受 `local`、`develop`、`production`，其他值会抛异常。
- `CheckRole` 支持控制器 `Privileges` 常量和静态属性，新增权限相关行为时两种形式都要考虑。

## 开发约定

- 保持包的 public API 稳定，尤其是 `PdAuth\Auth`、`PdAuth\Controller` 和两个中间件。
- 不要把 Laravel 应用完整启动作为单元测试前提。现有测试使用轻量 fake 保持速度和隔离性。
- 修改 `src/` 行为时同步补 `tests/Unit/`，并跑 `composer test:coverage`。
- 不要让 PHPUnit 执行 `tests/1.php`。
- 只在需要同步依赖时修改 `composer.lock`，避免无关依赖漂移。
- 工作区可能已有用户改动，编辑前先看 `git status --short`，不要回滚非本次任务的改动。
- 提交代码时 commit message 必须遵循 Conventional Commits，例如 `docs: update project documentation`、`fix: handle expired token`、`feat: add user group support`。

## 文档维护

更新功能或行为时同步更新：

- `README.md`: 面向使用者，说明安装、配置、用法、路由和测试。
- `AGENTS.md`: 面向维护者和 Agent，说明结构、命令、兼容点和注意事项。
