<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\NotFoundException;
use App\Core\TenantContext;

/**
 * Acesso aos dados do proprio tenant e de suas configuracoes.
 *
 * Diferente dos outros repositories, aqui a tabela `tenants` nao possui
 * `tenant_id`: a identidade da linha E o tenant. Por isso as consultas usam
 * `WHERE id = :tenant_id` / `WHERE tenant_id = :tenant_id` e continuam
 * passando pela guarda do TenantScopedRepository, que injeta o tenant ativo.
 */
final class TenantRepository extends TenantScopedRepository
{
    /**
     * @return array<string, mixed>
     */
    public function current(): array
    {
        $tenant = $this->selectOne(
            'SELECT id, name, document, email, phone, logo, status, created_at, updated_at
             FROM tenants
             WHERE id = :tenant_id'
        );

        if ($tenant === null) {
            throw new NotFoundException('Tenant nao encontrado.');
        }

        return $tenant;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function settings(): ?array
    {
        return $this->selectOne(
            'SELECT id, tenant_id, currency, timezone, language, date_format, created_at, updated_at
             FROM tenant_settings
             WHERE tenant_id = :tenant_id'
        );
    }

    /**
     * Garante que o tenant ativo tenha uma linha de configuracoes. Verifica
     * antes de inserir para ser portatil (o MySQL tem INSERT IGNORE, o SQLite
     * usado nos testes nao) e para nao repetir o placeholder `:tenant_id`.
     */
    public function ensureSettings(): void
    {
        if ($this->settings() !== null) {
            return;
        }

        $this->run(
            'INSERT INTO tenant_settings (tenant_id, currency, timezone, language, date_format, created_at, updated_at)
             VALUES (:tenant_id, :currency, :timezone, :language, :date_format, :created_at, :updated_at)',
            [
                'currency' => TenantContext::DEFAULT_CURRENCY,
                'timezone' => TenantContext::DEFAULT_TIMEZONE,
                'language' => TenantContext::DEFAULT_LANGUAGE,
                'date_format' => TenantContext::DEFAULT_DATE_FORMAT,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Grava as configuracoes do tenant ativo. O `:tenant_id` vem da sessao, e
     * nao ha parametro para escolher outro tenant.
     *
     * @param array<string, mixed> $data
     */
    public function saveSettings(array $data): void
    {
        $this->run(
            'UPDATE tenant_settings
             SET currency = :currency,
                 timezone = :timezone,
                 language = :language,
                 date_format = :date_format,
                 updated_at = :updated_at
             WHERE tenant_id = :tenant_id',
            [
                'currency' => $data['currency'],
                'timezone' => $data['timezone'],
                'language' => $data['language'],
                'date_format' => $data['date_format'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Criacao de tenant e configuracao inicial. E o unico ponto sem tenant na
     * sessao, o que so pode ser feito no cadastro (equivalente a
     * AuditLogRepository::createForTenant). Nao ha rota de UI para isso ainda;
     * o cadastro publico pertence ao painel do SaaS (P2 da Etapa 2.3).
     *
     * @param array<string, mixed> $tenant
     * @param array<string, mixed> $settings
     */
    public function create(array $tenant, array $settings = []): int
    {
        $pdo = $this->pdo();
        $now = date('Y-m-d H:i:s');
        $pdo->beginTransaction();

        try {
            $statement = $pdo->prepare(
                'INSERT INTO tenants (name, document, email, phone, logo, status, created_at, updated_at)
                 VALUES (:name, :document, :email, :phone, :logo, :status, :created_at, :updated_at)'
            );
            $statement->execute([
                'name' => $tenant['name'],
                'document' => $tenant['document'] ?? null,
                'email' => $tenant['email'] ?? null,
                'phone' => $tenant['phone'] ?? null,
                'logo' => $tenant['logo'] ?? null,
                'status' => $tenant['status'] ?? 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $tenantId = (int) $pdo->lastInsertId();

            $statement = $pdo->prepare(
                'INSERT INTO tenant_settings (tenant_id, currency, timezone, language, date_format, created_at, updated_at)
                 VALUES (:tenant_id, :currency, :timezone, :language, :date_format, :created_at, :updated_at)'
            );
            $statement->execute([
                'tenant_id' => $tenantId,
                'currency' => $settings['currency'] ?? TenantContext::DEFAULT_CURRENCY,
                'timezone' => $settings['timezone'] ?? TenantContext::DEFAULT_TIMEZONE,
                'language' => $settings['language'] ?? TenantContext::DEFAULT_LANGUAGE,
                'date_format' => $settings['date_format'] ?? TenantContext::DEFAULT_DATE_FORMAT,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $pdo->commit();
        } catch (\Throwable $throwable) {
            $pdo->rollBack();

            throw $throwable;
        }

        return $tenantId;
    }
}
