<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAccess
{
    public function handle(Request $request, Closure $next, string $menuKey, string $action = 'view')
    {
        abort_unless($request->user()?->canAccess($menuKey, $action), 403, 'You do not have permission to access this page.');

        return $next($request);
    }
}
