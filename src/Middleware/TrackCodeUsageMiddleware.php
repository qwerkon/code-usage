<?php

namespace Qwerkon\CodeUsage\Middleware;

use Qwerkon\CodeUsage\Contracts\CodeUsageTracker;
use Closure;
use Illuminate\Http\Request;

class TrackCodeUsageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $route = $request->route();

        if ($route) {
            $action = $route->getActionName() ?: 'closure';
            $tracker = app(CodeUsageTracker::class);
            $tracker->track($action, 'controller', [
                'route' => $route->getName(),
            ]);
        }

        return $response;
    }
}
