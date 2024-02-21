<?php

namespace App\Policies;

use App\Models\Round;
use App\Models\User;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function viewAny(User $user)
    {
        return $user->is_admin;
    }

    public function viewRounds(User $user)
    {
        return $user->is_round_operator;
    }
}
