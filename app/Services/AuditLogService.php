<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * Record an append-only audit event.
     */
    public function log(
        Model $auditable,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null
    ): AuditLog {
        $actor = $user ?? Auth::user();

        return AuditLog::create([
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->getKey(),
            'user_id' => $actor?->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}

