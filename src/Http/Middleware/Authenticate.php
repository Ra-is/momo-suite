<?php

namespace Rais\MomoSuite\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
    {
        return $request->expectsJson() ? null : route('momo.login');
    }

    /**
     * Get the guard that should be used for authentication.
     */
    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = ['momo'];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }

        $this->unauthenticated($request, $guards);
    }
}
