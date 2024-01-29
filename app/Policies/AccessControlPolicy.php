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

    public function update(?User $user)
    {
        // Check if running in CLI mode (Artisan command)
        if (php_sapi_name() == 'cli') {
            return true;
        }

        $accessControl = $user->accessControl;
        return $accessControl && $accessControl->role === 'admin';
    }
}
