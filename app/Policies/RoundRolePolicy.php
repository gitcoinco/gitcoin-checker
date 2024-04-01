<?php

namespace App\Policies;

use App\Models\RoundRole;
use App\Models\User;

class RoundRolePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function canDeleteRoundRole(User $user, RoundRole $roundRole)
    {
        $round = $roundRole->round;
        return $user->is_admin || $user->roundRoles()->where('round_id', $round->id)->where('role', 'MANAGER')->count() > 0;
    }
}
