<?php

namespace App\Providers;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Policies\NotificationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\CompanyInfo;
use App\Observers\CompanyInfoObserver;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(NotificationPreference::class, NotificationPolicy::class);
        CompanyInfo::observe(CompanyInfoObserver::class);
    }
}
