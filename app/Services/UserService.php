<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditLogRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class UserService
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
        private readonly AuditLogRepository $auditLogs,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->users->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $userId): array
    {
        return $this->users->findOrFail($userId);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');

        if ($name === '') {
            throw new RuntimeException('Campo obrigatorio ausente: name');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('E-mail invalido.');
        }

        $this->assertPasswordAcceptable($password);
        $this->assertEmailAvailable($email);
        $this->assertRoleBelongsToTenant($this->requiredInt($data, 'role_id'));

        $user = $this->users->create([
            'role_id' => (int) $data['role_id'],
            'name' => $name,
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'status' => UserRepository::STATUS_ACTIVE,
        ]);

        $this->auditLogs->create('users.create', 'user', (int) $user['id'], ['role_id' => $user['role_id']]);

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function update(int $userId, array $data): array
    {
        $current = $this->users->findOrFail($userId);

        if (isset($data['email'])) {
            $email = mb_strtolower(trim((string) $data['email']));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('E-mail invalido.');
            }

            $this->assertEmailAvailable($email, $userId);
            $data['email'] = $email;
        }

        if (array_key_exists('status', $data)) {
            $status = strtoupper((string) $data['status']);

            if (!in_array($status, [UserRepository::STATUS_ACTIVE, UserRepository::STATUS_INACTIVE], true)) {
                throw new RuntimeException('Status invalido.');
            }

            $data['status'] = $status;
        }

        $newRoleId = isset($data['role_id']) ? (int) $data['role_id'] : (int) $current['role_id'];

        if (isset($data['role_id'])) {
            $this->assertRoleBelongsToTenant($newRoleId);
        }

        $this->assertKeepsAnActiveAdmin($userId, $current, $data['status'] ?? null, $newRoleId);

        $user = $this->users->update($userId, $data);

        $this->auditLogs->create('users.update', 'user', $userId);

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function changePassword(int $userId, string $password): array
    {
        $this->users->findOrFail($userId);
        $this->assertPasswordAcceptable($password);

        $this->users->updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
        $this->auditLogs->create('users.password_change', 'user', $userId);

        return $this->users->findOrFail($userId);
    }

    /**
     * @return array<string, mixed>
     */
    public function block(int $userId): array
    {
        return $this->update($userId, ['status' => UserRepository::STATUS_INACTIVE]);
    }

    /**
     * @return array<string, mixed>
     */
    public function activate(int $userId): array
    {
        return $this->update($userId, ['status' => UserRepository::STATUS_ACTIVE]);
    }

    /**
     * O tenant precisa conservar ao menos um usuario ativo com permissao de
     * gestao de usuarios. Verifica tanto desativacao quanto troca de perfil,
     * porque as duas podem remover a condicao. Só importa quando o usuario ja
     * e hoje um administrador ativo: remover o perfil de quem ja esta inativo
     * nao reduz a contagem.
     *
     * @param array<string, mixed> $current
     */
    private function assertKeepsAnActiveAdmin(
        int $userId,
        array $current,
        ?string $newStatus,
        int $newRoleId,
    ): void {
        $isActiveNow = ($current['status'] ?? null) === UserRepository::STATUS_ACTIVE;

        if (!$isActiveNow || !$this->users->hasAdminPermission($userId)) {
            return;
        }

        $staysActive = $newStatus !== UserRepository::STATUS_INACTIVE;
        $newRoleIsAdmin = $this->roles->hasPermission($newRoleId, UserRepository::ADMIN_PERMISSION);

        if ($staysActive && $newRoleIsAdmin) {
            return;
        }

        if ($this->users->countActiveAdmins() <= 1) {
            throw new RuntimeException(
                'Nao e possivel remover o ultimo administrador ativo do tenant.'
            );
        }
    }

    private function assertEmailAvailable(string $email, ?int $ignoreUserId = null): void
    {
        if ($this->users->emailExistsInAnyTenant($email, $ignoreUserId)) {
            throw new RuntimeException('Este e-mail ja esta em uso.');
        }
    }

    private function assertRoleBelongsToTenant(int $roleId): void
    {
        $role = $this->roles->find($roleId);

        if ($role === null) {
            throw new RuntimeException('Perfil invalido.');
        }
    }

    private function assertPasswordAcceptable(string $password): void
    {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new RuntimeException(
                'A senha deve ter ao menos ' . self::MIN_PASSWORD_LENGTH . ' caracteres.'
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requiredInt(array $data, string $field): int
    {
        if (!isset($data[$field]) || (int) $data[$field] <= 0) {
            throw new RuntimeException('Campo obrigatorio ausente: ' . $field);
        }

        return (int) $data[$field];
    }
}
