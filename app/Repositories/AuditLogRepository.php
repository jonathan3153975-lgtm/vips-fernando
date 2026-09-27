<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Application;
use App\Core\Database;

final class AuditLogRepository
{
    public function create(
        ?int $tenantId,
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        array $metadata = []
    ): void {
        $app = Application::getInstance();
        $pdo = Database::connect($app->config('database'));

        $statement = $pdo->prepare(
            'INSERT INTO audit_logs (
                tenant_id,
                user_id,
                action,
                entity_type,
                entity_id,
                metadata,
                created_at
            ) VALUES (
                :tenant_id,
                :user_id,
                :action,
                :entity_type,
                :entity_id,
                :metadata,
                :created_at
            )'
        );

        $statement->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
