<?php

declare(strict_types=1);

namespace App\Repositories;

final class AuditLogRepository extends TenantScopedRepository
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function create(
        string $action,
        string $entityType,
        ?int $entityId,
        array $metadata = [],
        ?int $userId = null,
    ): void {
        $this->write(
            $this->tenantId(),
            $userId ?? $this->userId(),
            $action,
            $entityType,
            $entityId,
            $metadata,
        );
    }

    /**
     * Eventos anteriores a autenticacao, em que ainda nao existe tenant na
     * sessao. O tenant vem do usuario localizado no proprio banco, nunca da
     * requisicao. Escrita apenas; nenhuma leitura usa esta via.
     *
     * @param array<string, mixed> $metadata
     */
    public function createForTenant(
        int $tenantId,
        int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        array $metadata = [],
    ): void {
        $this->write($tenantId, $userId, $action, $entityType, $entityId, $metadata);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function write(
        int $tenantId,
        int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        array $metadata,
    ): void {
        $statement = $this->pdo()->prepare(
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
