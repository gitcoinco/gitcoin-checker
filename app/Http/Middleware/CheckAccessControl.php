<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import the DB facade to run database queries

class CheckAccessControl
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user(); // Get the authenticated user

        $hasAccessControl = $user->accessControl()->exists();

        if (!$hasAccessControl) {
            // redirect to no access page
            return redirect()->route('noaccess');
        }

        return $next($request); // If the user exists, proceed with the request
    }
}
