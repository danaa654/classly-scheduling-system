<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next) {
    // If user is a Dean/OIC, they must have a department assigned
    if (in_array(auth()->user()->role, ['dean', 'oic']) && !auth()->user()->department) {
        abort(403, 'Department not assigned to this official.');
    }
    return $next($request);
}
}
