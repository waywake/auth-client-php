<?php

namespace PdAuth\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Str;

class Authenticate
{

    const CookieName = 'token';

    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        //oauth 回调
        $code = $request->input('pd_code');
        if ($code) {
            $token = app('pd.auth')->getAccessToken($code);
            if (isset($token['access_token'])) {
                setcookie(self::CookieName, $token['access_token'], strtotime($token['expired_at']));

                $qs = $request->getQueryString();
                $params = explode('&', $qs);
                $qs = '?';
                foreach ($params as $k => $v) {
                    if (Str::startsWith($v, 'pd_code=')) {
                        continue;
                    }
                    $qs .= $v . '&';
                }
                abort(302, '', [
                    'Location' => $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo() . $qs,
                ]);
            }
        }

        //登录状态检测
        if ($this->auth->guard($guard)->guest()) {
            if ($request->isXmlHttpRequest()) {
                return response()->json([
                    'code' => 401,
                    'msg' => 'need login',
                    'data' => null,
                ]);
            }
            return redirect(app('pd.auth')->connect($request->getUri()));
        }

        //权限检测
        $path = $request->path();
        $privileges = config('pdauth.roles_privileges');
        $user = $request->user();
        $match = [];
        foreach ($user['roles'] as $role) {
            if (array_key_exists($role, $privileges)) {
                $match += $privileges[$role];
            }
        }

        if (in_array($path, $match)) {
            return $next($request);
        }

        if ($request->isXmlHttpRequest()) {
            return response()->json([
                'code' => 403,
                'msg' => '无权访问，请联系管理员授权',
                'data' => null,
            ]);
        }
        api_abort(403, '无权访问，请联系管理员授权');
        return $next($request);
    }
}
