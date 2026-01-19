<?php

namespace App\Providers;


use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Policies\NotificationPolicy;
use App\Policies\NotificationPreferencePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{   

    protected $policies = [
        Notification::class => NotificationPolicy::class,
        NotificationPreference::class => NotificationPreferencePolicy::class,
        // ... vos autres policies
    ];
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
