<?php

namespace cores\middleware;

use cores\driver\TokenAuth;
use cores\exception\UnAuthenticatedException;
use cores\provider\UserProvider;
use cores\Request;

class Authenticate
{

    /**
     * @throws UnAuthenticatedException
     */
    public function handle(Request $request, \Closure $next)
    {

        $tokenAuth = new TokenAuth($request, app(UserProvider::class));

        $tokenAuth->authenticate();

        $request->setUserResolver(function () use ($tokenAuth) {
            return $tokenAuth->user();
        });

        $request->setTokenResolver(function () use ($tokenAuth) {
            return $tokenAuth->token();
        });

        return $next($request);
    }

}