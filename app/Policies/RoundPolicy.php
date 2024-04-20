<?php

namespace App\Policies;

use App\Models\Round;
use App\Models\User;

class RoundPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function canEditRoundRole(User $user, Round $round)
    {
        return $user->is_admin || $user->roundRoles()->where('round_id', $round->id)->where('role', 'MANAGER')->count() > 0;
    }

    public function view(User $user, Round $round)
    {
        return $user->is_admin || $user->roundRoles()->where('round_id', $round->id)->count() > 0;
    }

    public function update(User $user, Round $round)
    {
        return $user->is_admin || $user->roundRoles()->where('round_id', $round->id)->count() > 0;
    }
}
