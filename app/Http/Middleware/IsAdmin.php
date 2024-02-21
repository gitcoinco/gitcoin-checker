<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is authenticated and an admin
        if (Auth::check() && Auth::user()->is_admin) {
            return $next($request);
        }
        // Optionally, redirect to a specific route or return an error
        return redirect('/noaccess')->with('error', 'You do not have access to this section');
    }
}
