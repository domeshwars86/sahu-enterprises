<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAdminPermission('notifications.view'), 403);

        return Inertia::render('Admin/Notifications/Index', [
            'unreadCount' => $request->user()->unreadNotifications()->count(),
            'notifications' => $request->user()->notifications()->latest()->paginate(30)->through(fn ($notification): array => ['id' => $notification->id, 'type' => class_basename($notification->type), 'data' => $notification->data, 'readAt' => $notification->read_at?->toIso8601String(), 'createdAt' => $notification->created_at?->toIso8601String()]),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('notifications.view'), 403);
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAdminPermission('notifications.view'), 403);
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Notifications marked as read.');
    }
}
