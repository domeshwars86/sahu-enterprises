<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->adminRole?->name,
                    'permissions' => $request->user()->adminRole?->permissions ?? [],
                ] : null,
            ],
            'notificationCount' => $request->user()?->hasAdminPermission('notifications.view')
                ? $request->user()->unreadNotifications()->count()
                : 0,
        ];
    }
}
