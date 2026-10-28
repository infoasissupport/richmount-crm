<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action in the audit trail.
     *
     * @param string $action Action name (created, updated, approved, rejected, deleted, etc.)
     * @param mixed $entity The model entity or name
     * @param string|null $description Human-readable note
     * @param array|null $oldValues Previous state
     * @param array|null $newValues New state
     * @param int|null $userId Specific user ID or defaults to authenticated user
     * @return AuditLog
     */
    public static function log(
        string $action,
        mixed $entity = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): AuditLog {
        $entityType = null;
        $entityId = null;

        if (is_object($entity)) {
            $entityType = class_basename($entity);
            $entityId = $entity->id ?? null;
        } elseif (is_string($entity)) {
            $entityType = $entity;
        }

        return AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
