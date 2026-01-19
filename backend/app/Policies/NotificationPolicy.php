<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPolicy
{
    /**
     * Vérifier si l'utilisateur peut voir la notification
     */
    public function view(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    /**
     * Vérifier si l'utilisateur peut mettre à jour la notification
     */
    public function update(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    /**
     * Vérifier si l'utilisateur peut supprimer la notification
     */
    public function delete(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }
}

