<?php

namespace NikunjKothiya\QueueMonitor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeQueueMonitor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->can('viewQueueMonitor')) {
            abort(403);
        }

        return $next($request);
    }
}


