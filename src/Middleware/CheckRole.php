<?php

namespace PdAuth\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{

    public function handle(Request $request, Closure $next)
    {
        $route = $request->route();
        $uses = null;

        if (is_array($route)) {
            $uses = $route[1]['uses'] ?? null;
        } elseif (is_object($route)) {
            if (method_exists($route, 'getActionName')) {
                $uses = $route->getActionName();
            } elseif (method_exists($route, 'getAction')) {
                $action = $route->getAction();
                $uses = $action['uses'] ?? null;
            }
        }

        if (!is_string($uses) || $uses === '' || $uses === 'Closure') {
            abort(403, '未定义权限');
        }

        if (str_contains($uses, '@')) {
            [$controller, $action] = explode('@', $uses, 2);
        } else {
            $controller = $uses;
            $action = '__invoke';
        }

        if (!class_exists($controller)
            || (!defined($controller . '::Privileges') && !property_exists($controller, 'Privileges'))
        ) {
            abort(403, '未定义权限');
        }

        $roles = $controller::Privileges;

        if (empty($roles) || empty($roles[$action])) {
            abort(403, '未定义权限');
        }

        if (is_string($roles[$action]) && $roles[$action] == '*') {
            return $next($request);
        }

        $user = $request->user();

        if (empty($user) || empty($user['roles'])) {
            abort(403, '无权访问');
        }

        if (empty(array_intersect($roles[$action], $user['roles']))) {
            abort(403, '无权访问，请联系管理员授权');
        }

        return $next($request);
    }

}
