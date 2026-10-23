<?php

declare(strict_types=1);

namespace App\Audit;

use App\Database\Connection;

final class AuditLogger
{
    public static function log(
        ?int $userId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): void {
        $stmt = Connection::get()->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address)
             VALUES (:user_id, :action, :entity_type, :entity_id, :metadata, :ip)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata ? json_encode($metadata, JSON_THROW_ON_ERROR) : null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
