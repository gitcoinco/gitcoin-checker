<?php

namespace App\Policies;

use App\Models\User;

class AccessControlPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user)
    {
        $accessControl = $user->accessControl;
        return $accessControl && $accessControl->role === 'admin';
    }
}
