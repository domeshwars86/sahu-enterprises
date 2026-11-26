<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasAdminPermission('audit.view'), 403);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => AuditLog::query()->with('user:id,name')->latest()->paginate(50)->withQueryString()->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'entity' => $log->entity_type ? $log->entity_type.($log->entity_id ? ' #'.$log->entity_id : '') : '—',
                'actor' => $log->user?->name ?? 'System',
                'metadata' => $log->metadata ?? [],
                'ipFingerprint' => $log->ip_fingerprint ? substr($log->ip_fingerprint, 0, 12).'…' : null,
                'createdAt' => $log->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
