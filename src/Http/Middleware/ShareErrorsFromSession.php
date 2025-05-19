<?php

namespace Rais\MomoSuite\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

class ShareErrorsFromSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Share errors with all views
        view()->share('errors', session()->get('errors') ?? new ViewErrorBag);

        return $next($request);
    }
}
