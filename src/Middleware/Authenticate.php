<?php

namespace PdAuth\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

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
        //登录状态检测
        if ($this->auth->guard($guard)->guest()) {
            $redirect = $request->input('redirect');
            if ($request->isXmlHttpRequest()) {
                if( $redirect == null ){
                    $redirect = $request->header('referer');
                }
                return response()->json([
                    'code' => config('pdauth.code.unauthorized', 401),
                    'msg' => 'Unauthorized',
                    'data' => [
                        'url' => app('pd.auth')->connect($redirect),
                    ],
                ],401);
            } else {
                if( $redirect == null ){
                    $redirect = $request->getSchemeAndHttpHost()."/api/auth/token.html";
                }
                return redirect(app('pd.auth')->connect($redirect));
            }
        }

        return $next($request);
    }
}
