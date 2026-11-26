<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogger
{
    /** @param array<string, scalar|null> $metadata */
    public function record(string $action, ?User $user = null, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'ip_fingerprint' => request()->ip() ? hash_hmac('sha256', request()->ip(), (string) config('app.key')) : null,
        ]);
    }
}
