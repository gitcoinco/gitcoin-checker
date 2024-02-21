<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;

class CheckAccessControl
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

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

        // if (!app()->runningUnitTests()) {
        //     if ($user->is_admin || $user->is_round_operator) {
        //     } else {
        //         // redirect to no access page
        //         return redirect()->route('noaccess');
        //     }
        // }

        // If the user doesn't have an email and name specified, redirect to the profile page
        if (!$user->email || !$user->name) {
            $this->notificationService->info('Please update your profile information.');
            return redirect()->route('profile.show');
        }

        return $next($request); // If the user exists, proceed with the request
    }
}
