<?php

namespace App\Policies;

use App\Models\NotificationSetup;
use App\Models\User;

class NotificationSetupPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function update(User $user, NotificationSetup $notificationSetup)
    {
        return $user->id === $notificationSetup->user_id;
    }

    public function delete(User $user, NotificationSetup $notificationSetup)
    {
        return $user->id === $notificationSetup->user_id;
    }
}
