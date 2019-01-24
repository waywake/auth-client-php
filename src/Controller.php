<?php

namespace PdAuth;


use PdAuth\Middleware\Authenticate;
use PdAuth\Middleware\CheckRole;

trait Controller
{

    protected $user;
    protected $guard;

    public function auth($guard)
    {
        $this->guard = $guard;
        app('auth')->shouldUse($guard);
        $this->middleware(Authenticate::class);
        $this->middleware(CheckRole::class);

        $this->user = app('request')->user($guard);
    }
}
