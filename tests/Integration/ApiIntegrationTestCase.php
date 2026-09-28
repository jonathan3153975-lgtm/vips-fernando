<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Application;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Router;
use App\Core\Session;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class ApiIntegrationTestCase extends TestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = sys_get_temp_dir() . '/importcontrol-test-' . bin2hex(random_bytes(6)) . '.sqlite';

        $_ENV['APP_NAME'] = 'ImportControl Test';
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['APP_URL'] = 'http://localhost';
        $_ENV['APP_TIMEZONE'] = 'America/Sao_Paulo';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $this->databasePath;

        $_SERVER['APP_NAME'] = $_ENV['APP_NAME'];
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'];
        $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'];
        $_SERVER['APP_URL'] = $_ENV['APP_URL'];
        $_SERVER['APP_TIMEZONE'] = $_ENV['APP_TIMEZONE'];
        $_SERVER['DB_CONNECTION'] = $_ENV['DB_CONNECTION'];
        $_SERVER['DB_DATABASE'] = $_ENV['DB_DATABASE'];

        unset($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_X_CSRF_TOKEN']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        session_id(bin2hex(random_bytes(8)));
        session_start();
        $_SESSION = [];

        $this->createSchema();
        $this->seedBaseData();
    }

    protected function tearDown(): void
    {
        Database::disconnectAll();
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        if (file_exists($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    protected function dispatchJson(string $method, string $uri, array $payload = []): array
    {
        $_POST = [];
        $_GET = [];
        parse_str((string) (parse_url($uri, PHP_URL_QUERY) ?? ''), $_GET);
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['__BODY__'] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        return $this->dispatch();
    }

    protected function dispatchForm(string $method, string $uri, array $form = []): array
    {
        $_POST = $form;
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        unset($_SERVER['__BODY__']);

        return $this->dispatch();
    }

    /**
     * Requisicao de pagina (sem payload), usada para conferir as telas
     * server-rendered. A query string da URI vira $_GET, para o controller ler
     * parametros como o token de redefinicao.
     */
    protected function dispatchPage(string $method, string $uri): array
    {
        $_POST = [];
        $_GET = [];
        parse_str((string) (parse_url($uri, PHP_URL_QUERY) ?? ''), $_GET);
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI'] = $uri;
        unset($_SERVER['CONTENT_TYPE'], $_SERVER['__BODY__'], $_SERVER['HTTP_REFERER'], $_SERVER['HTTP_X_CSRF_TOKEN']);

        return $this->dispatch();
    }

    /**
     * POST de formulario nativo: x-www-form-urlencoded + token CSRF, como o
     * navegador faz. Rotas /api/ rejeitam esse content-type com 415, entao
     * exercita as rotas web.
     */
    protected function dispatchUserForm(string $method, string $uri, array $form = []): array
    {
        return $this->dispatchForm($method, $uri, [Csrf::FIELD_NAME => $this->csrfToken()] + $form);
    }

    /**
     * Igual a dispatchUserForm, mas com o corpo urlencoded preenchido, como o
     * SAPI real faz. Sem isso php://input fica vazio no CLI e o teste passa
     * por um caminho que o servidor nunca percorre.
     */
    protected function dispatchNativeForm(string $method, string $uri, array $form = []): array
    {
        $form = [Csrf::FIELD_NAME => $this->csrfToken()] + $form;

        $_POST = $form;
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $_SERVER['__BODY__'] = http_build_query($form);

        return $this->dispatch();
    }

    protected function csrfToken(): string
    {        (new Session())->touch();

        return (new Session())->token();
    }

    /**
     * Reconstroi a aplicacao com uma configuracao de banco alternativa e
     * despacha a rota, sem alterar a configuracao usada pelos demais testes.
     */
    protected function dispatchWithDatabaseConfig(array $database, string $method, string $uri): array
    {
        $config = $this->config();
        $config['database'] = $database;

        new Application($config, dirname(__DIR__, 2) . '/routes/web.php');
        $router = new Router();
        require dirname(__DIR__, 2) . '/routes/web.php';

        return $router->dispatch(strtoupper($method), $uri);
    }

    protected function dispatch(): array
    {
        new Application($this->config(), dirname(__DIR__, 2) . '/routes/web.php');
        $router = new Router();
        require dirname(__DIR__, 2) . '/routes/web.php';

        // Em producao o Application::resolvePath() remove a query string; aqui
        // fazemos o mesmo para o roteamento bater com o servidor real.
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';

        return $router->dispatch(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), $path);
    }

    /**
     * Registra a Application com o banco do teste, para exercitar repositories
     * e services diretamente, sem passar pelo router.
     */
    protected function bootApplication(): void
    {
        new Application($this->config(), dirname(__DIR__, 2) . '/routes/web.php');
    }

    protected function responseJson(array $response): array
    {
        $decoded = json_decode($response['body'], true);

        return is_array($decoded) ? $decoded : [];
    }

    private function config(): array
    {
        return [
            'app' => [
                'name' => $_ENV['APP_NAME'],
                'env' => $_ENV['APP_ENV'],
                'debug' => true,
                'url' => $_ENV['APP_URL'],
                'timezone' => $_ENV['APP_TIMEZONE'],
                'session' => [
                    'driver' => 'file',
                    'lifetime' => 120,
                    'cookie' => 'importcontrol_test_session',
                ],
            ],
            'database' => [
                'default' => 'sqlite',
                'connections' => [
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => $this->databasePath,
                    ],
                ],
            ],
        ];
    }

    private function createSchema(): void
    {
        $pdo = $this->pdo();

        $statements = [
            'CREATE TABLE tenants (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, document TEXT, email TEXT, phone TEXT, logo TEXT, status TEXT NOT NULL, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE tenant_settings (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL UNIQUE, currency TEXT NOT NULL DEFAULT \'BRL\', timezone TEXT NOT NULL DEFAULT \'America/Sao_Paulo\', language TEXT NOT NULL DEFAULT \'pt-BR\', date_format TEXT NOT NULL DEFAULT \'d/m/Y\', created_at TEXT, updated_at TEXT)',
            'CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER, name TEXT NOT NULL, description TEXT, is_system INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE permissions (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL UNIQUE, description TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE role_permissions (id INTEGER PRIMARY KEY AUTOINCREMENT, role_id INTEGER NOT NULL, permission_id INTEGER NOT NULL, created_at TEXT)',
            'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, role_id INTEGER NOT NULL, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, phone TEXT, password TEXT NOT NULL, auth_version INTEGER NOT NULL DEFAULT 0, avatar TEXT, status TEXT NOT NULL, last_login TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE password_resets (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, token TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL, used_at TEXT, created_at TEXT)',
            'CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER, user_id INTEGER, action TEXT NOT NULL, entity_type TEXT NOT NULL, entity_id INTEGER, metadata TEXT, created_at TEXT)',
            'CREATE TABLE suppliers (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT NOT NULL, country TEXT, city TEXT, contact_name TEXT, email TEXT, phone TEXT, notes TEXT, status TEXT NOT NULL, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE imports (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, responsible_user_id INTEGER, name TEXT NOT NULL, description TEXT, country TEXT NOT NULL, city TEXT, start_date TEXT NOT NULL, end_date TEXT, currency TEXT NOT NULL, exchange_rate REAL NOT NULL, status TEXT NOT NULL, allocation_method TEXT NOT NULL DEFAULT \'VALUE\', invested_amount REAL NOT NULL DEFAULT 0, total_expenses REAL NOT NULL DEFAULT 0, total_items REAL NOT NULL DEFAULT 0, completed_at TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE exchange_rates (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, currency TEXT NOT NULL, rate REAL NOT NULL, reference_date TEXT NOT NULL, source TEXT, created_at TEXT, UNIQUE(tenant_id, currency, reference_date))',
            'CREATE TABLE import_expenses (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, import_id INTEGER NOT NULL, supplier_id INTEGER, category TEXT NOT NULL, description TEXT NOT NULL, currency TEXT NOT NULL, amount REAL NOT NULL, exchange_rate REAL NOT NULL, converted_amount REAL NOT NULL, expense_date TEXT NOT NULL, status TEXT NOT NULL, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE import_items (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, import_id INTEGER NOT NULL, supplier_id INTEGER, product_name TEXT NOT NULL, sku TEXT, quantity REAL NOT NULL, unit_cost_foreign REAL NOT NULL, exchange_rate REAL NOT NULL, unit_cost_local REAL NOT NULL, total_cost_local REAL NOT NULL, allocated_expense REAL NOT NULL DEFAULT 0, real_unit_cost REAL NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, category_id INTEGER, brand_id INTEGER, supplier_id INTEGER, default_import_item_id INTEGER, sku TEXT NOT NULL, barcode TEXT, name TEXT NOT NULL, description TEXT, unit TEXT NOT NULL, status TEXT NOT NULL, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE product_prices (id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER NOT NULL UNIQUE, cost_price REAL NOT NULL DEFAULT 0, sale_price REAL NOT NULL DEFAULT 0, minimum_price REAL NOT NULL DEFAULT 0, margin REAL NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE stock (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, product_id INTEGER NOT NULL, quantity REAL NOT NULL DEFAULT 0, reserved_quantity REAL NOT NULL DEFAULT 0, minimum_quantity REAL NOT NULL DEFAULT 0, updated_at TEXT, UNIQUE(tenant_id, product_id))',
        ];

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
    }

    private function seedBaseData(): void
    {
        $pdo = $this->pdo();
        $now = date('Y-m-d H:i:s');

        $pdo->exec("INSERT INTO tenants (id, name, status, created_at, updated_at) VALUES (1, 'Tenant One', 'ACTIVE', '$now', '$now')");
        $pdo->exec("INSERT INTO tenants (id, name, status, created_at, updated_at) VALUES (2, 'Tenant Two', 'ACTIVE', '$now', '$now')");

        // Configuracoes diferentes por tenant: prova que a exibicao e o escopo
        // nao vazam de um para o outro.
        $pdo->exec("INSERT INTO tenant_settings (tenant_id, currency, timezone, language, date_format, created_at, updated_at) VALUES (1, 'BRL', 'America/Sao_Paulo', 'pt-BR', 'd/m/Y', '$now', '$now')");
        $pdo->exec("INSERT INTO tenant_settings (tenant_id, currency, timezone, language, date_format, created_at, updated_at) VALUES (2, 'USD', 'America/New_York', 'en-US', 'm/d/Y', '$now', '$now')");

        $pdo->exec("INSERT INTO roles (id, tenant_id, name, description, is_system, created_at, updated_at) VALUES (1, 1, 'admin', 'Admin Tenant 1', 1, '$now', '$now')");
        $pdo->exec("INSERT INTO roles (id, tenant_id, name, description, is_system, created_at, updated_at) VALUES (2, 1, 'viewer', 'Viewer Tenant 1', 1, '$now', '$now')");
        $pdo->exec("INSERT INTO roles (id, tenant_id, name, description, is_system, created_at, updated_at) VALUES (3, 2, 'admin', 'Admin Tenant 2', 1, '$now', '$now')");

        $permissions = [
            1 => 'dashboard.view',
            2 => 'imports.view',
            3 => 'imports.create',
            4 => 'imports.complete',
            5 => 'products.view',
            6 => 'products.create',
            7 => 'products.edit',
            8 => 'stock.view',
            9 => 'users.view',
            10 => 'users.manage',
            11 => 'settings.manage',
        ];

        foreach ($permissions as $id => $name) {
            $statement = $pdo->prepare('INSERT INTO permissions (id, name, description, created_at, updated_at) VALUES (:id, :name, :description, :created_at, :updated_at)');
            $statement->execute([
                'id' => $id,
                'name' => $name,
                'description' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // viewer (role 2) fica restrito a imports.view, para exercitar o 403.
        $grants = [[1, 1], [1, 2], [1, 3], [1, 4], [1, 5], [1, 6], [1, 7], [1, 8], [1, 9], [1, 10], [1, 11], [2, 2], [3, 1], [3, 2], [3, 3], [3, 4], [3, 5], [3, 6], [3, 7], [3, 8], [3, 9], [3, 10], [3, 11]];

        foreach ($grants as [$roleId, $permissionId]) {
            $statement = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id, created_at) VALUES (:role_id, :permission_id, :created_at)');
            $statement->execute([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
            ]);
        }

        $users = [
            [1, 1, 'Admin One', 'admin1@example.com', 'ACTIVE'],
            [2, 2, 'Viewer One', 'viewer1@example.com', 'ACTIVE'],
            [3, 3, 'Admin Two', 'admin2@example.com', 'ACTIVE'],
        ];

        foreach ($users as [$id, $roleId, $name, $email, $status]) {
            $tenantId = $id === 3 ? 2 : 1;
            $statement = $pdo->prepare(
                'INSERT INTO users (id, tenant_id, role_id, name, email, phone, password, status, created_at, updated_at)
                 VALUES (:id, :tenant_id, :role_id, :name, :email, :phone, :password, :status, :created_at, :updated_at)'
            );
            $statement->execute([
                'id' => $id,
                'tenant_id' => $tenantId,
                'role_id' => $roleId,
                'name' => $name,
                'email' => $email,
                'phone' => '',
                'password' => password_hash('secret123', PASSWORD_DEFAULT),
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $pdo->exec("INSERT INTO imports (id, tenant_id, responsible_user_id, name, description, country, city, start_date, end_date, currency, exchange_rate, status, invested_amount, total_expenses, total_items, created_at, updated_at) VALUES (10, 2, 3, 'Importacao Tenant 2', 'Seed', 'US', 'Miami', '2026-08-01', '2026-08-05', 'USD', 5.40, 'PLANNED', 0, 0, 0, '$now', '$now')");
    }

    private function pdo(): PDO
    {
        return new PDO('sqlite:' . $this->databasePath);
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    protected function fetchAllRows(string $sql, array $params = []): array
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    protected function execSql(string $sql, array $params = []): int
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }
}
