<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::before(function ($user, string $ability): ?bool {
            if (method_exists($user, 'hasAdminPermission') && preg_match('/^(dashboard|enquiries|followups|tasks|applications|customers|reports|products|partners|content|news|videos|users|audit|settings|notifications|contact_messages|roles)\./', $ability)) {
                return $user->hasAdminPermission($ability);
            }

            return null;
        });
    }
}
