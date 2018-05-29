<?php

namespace PdAuth\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{

    public function handle(Request $request, Closure $next)
    {
        $uses = $request->route()[1]['uses'];
        list($controller, $action) = explode('@', $uses);
        $roles = $controller::Privileges;

        if (empty($roles) || empty($roles[$action])) {
            abort(403, '未定义权限');
        }

        if (is_string($roles[$action]) && $roles[$action] == '*') {
            return $next($request);
        }

        $user = $request->user('auth');

        if (empty($user) || empty($user['roles'])) {
            abort(403, '无权访问');
        }

        if (empty(array_intersect($roles[$action], $user['roles']))) {
            abort(403, '无权访问，请联系管理员授权');
        }

        return $next($request);
    }

}
