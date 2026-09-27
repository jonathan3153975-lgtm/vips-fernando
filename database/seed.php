<?php

declare(strict_types=1);

use App\Core\Database;

$app = require __DIR__ . '/../bootstrap/app.php';
$pdo = Database::connect($app->config('database'));

$roles = [
    'admin' => 'Administrador da empresa',
    'manager' => 'Gerente operacional',
    'sales' => 'Vendedor',
    'finance' => 'Financeiro',
    'inventory' => 'Estoque',
];

$permissions = [
    'dashboard.view',
    'imports.view',
    'imports.create',
    'imports.complete',
    'products.view',
    'products.create',
    'products.edit',
    'stock.view',
    'stock.adjust',
    'customers.view',
    'customers.create',
    'sales.view',
    'sales.create',
    'sales.discount',
    'sales.change_price',
    'sales.cancel',
    'sales.return',
    'financial.view',
    'financial.manage',
    'users.view',
    'users.manage',
];

$pdo->beginTransaction();

try {
    $pdo->exec(
        "INSERT INTO tenants (name, document, email, phone, status, created_at, updated_at)
         VALUES ('Tenant Demo', '00000000000191', 'contato@demo.local', '(11) 99999-9999', 'ACTIVE', NOW(), NOW())
         ON DUPLICATE KEY UPDATE email = VALUES(email), updated_at = NOW()"
    );

    $tenantId = (int) $pdo->query("SELECT id FROM tenants WHERE email = 'contato@demo.local' LIMIT 1")->fetchColumn();

    $tenantSettingsStatement = $pdo->prepare(
        'INSERT INTO tenant_settings (tenant_id, currency, timezone, language, date_format, created_at, updated_at)
         VALUES (:tenant_id, :currency, :timezone, :language, :date_format, NOW(), NOW())
         ON DUPLICATE KEY UPDATE currency = VALUES(currency), timezone = VALUES(timezone), language = VALUES(language), date_format = VALUES(date_format), updated_at = NOW()'
    );
    $tenantSettingsStatement->execute([
        'tenant_id' => $tenantId,
        'currency' => 'BRL',
        'timezone' => 'America/Sao_Paulo',
        'language' => 'pt-BR',
        'date_format' => 'd/m/Y',
    ]);

    $roleStatement = $pdo->prepare(
        'INSERT INTO roles (tenant_id, name, description, is_system, created_at, updated_at)
         VALUES (:tenant_id, :name, :description, 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = NOW()'
    );

    foreach ($roles as $name => $description) {
        $roleStatement->execute([
            'tenant_id' => $tenantId,
            'name' => $name,
            'description' => $description,
        ]);
    }

    $permissionStatement = $pdo->prepare(
        'INSERT INTO permissions (name, description, created_at, updated_at)
         VALUES (:name, :description, NOW(), NOW())
         ON DUPLICATE KEY UPDATE description = VALUES(description), updated_at = NOW()'
    );

    foreach ($permissions as $permission) {
        $permissionStatement->execute([
            'name' => $permission,
            'description' => $permission,
        ]);
    }

    $adminRoleId = (int) $pdo->query("SELECT id FROM roles WHERE tenant_id = {$tenantId} AND name = 'admin' LIMIT 1")->fetchColumn();

    $pdo->exec(
        "INSERT IGNORE INTO role_permissions (role_id, permission_id, created_at)
         SELECT {$adminRoleId}, p.id, NOW()
         FROM permissions p"
    );

    $userStatement = $pdo->prepare(
        'INSERT INTO users (tenant_id, role_id, name, email, phone, password, status, created_at, updated_at)
         VALUES (:tenant_id, :role_id, :name, :email, :phone, :password, :status, NOW(), NOW())
         ON DUPLICATE KEY UPDATE role_id = VALUES(role_id), name = VALUES(name), phone = VALUES(phone), password = VALUES(password), status = VALUES(status), updated_at = NOW()'
    );
    $userStatement->execute([
        'tenant_id' => $tenantId,
        'role_id' => $adminRoleId,
        'name' => 'Administrador Demo',
        'email' => 'admin@demo.local',
        'phone' => '(11) 99999-0000',
        'password' => password_hash('Admin@123', PASSWORD_DEFAULT),
        'status' => 'ACTIVE',
    ]);

    $pdo->commit();
    echo 'Seeds basicas aplicadas.' . PHP_EOL;
} catch (Throwable $throwable) {
    $pdo->rollBack();
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
}
