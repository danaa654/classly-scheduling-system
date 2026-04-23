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
    public function handle(Request $request, Closure $next, ...$roles)
{
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    // If the route doesn't specify roles (like a general /dashboard), just let them in
    if (empty($roles)) {
        return $next($request);
    }

    // Bridge OIC to Dean
    if ($user->role === 'oic' && in_array('dean', $roles)) {
        return $next($request);
    }

    // Direct match for associate_dean, etc.
    if (in_array($user->role, $roles)) {
        return $next($request);
    }

    abort(403, "Role mismatch: Found '{$user->role}'. Expected: " . implode(', ', $roles));
}
}
