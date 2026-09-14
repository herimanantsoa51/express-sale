<?php

namespace App\Policies;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferencePolicy
{
    /**
     * Determine if the user can view the notification preference.
     */
    public function view(User $user, NotificationPreference $preference): bool
    {
        return $user->id === $preference->user_id;
    }

    /**
     * Determine if the user can update the notification preference.
     */
    public function update(User $user, NotificationPreference $preference): bool
    {
        return $user->id === $preference->user_id;
    }

    /**
     * Determine if the user can delete the notification preference.
     */
    public function delete(User $user, NotificationPreference $preference): bool
    {
        return $user->id === $preference->user_id;
    }
}
