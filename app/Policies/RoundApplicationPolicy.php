<?php

namespace App\Policies;

use App\Models\RoundApplication;
use App\Models\User;

class RoundApplicationPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function canReview(User $user, RoundApplication $application)
    {
        return $user->is_admin || $user->roundRoles()->where('round_id', $application->round_id)->count() > 0;
    }

    public function view(User $user, RoundApplication $application)
    {
        return $user->is_admin || $user->roundRoles()->where('round_id', $application->round_id)->count() > 0;
    }
}
