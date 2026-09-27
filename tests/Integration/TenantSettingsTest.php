<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Session;
use App\Core\TenantContext;
use App\Repositories\TenantRepository;

final class TenantSettingsTest extends ApiIntegrationTestCase
{
    public function testSettingsPageRequiresManagePermission(): void
    {
        $this->loginAs('viewer1@example.com');

        self::assertSame(403, $this->dispatchPage('GET', '/configuracoes')['status']);
    }

    public function testSettingsPageShowsCurrentTenantSettings(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchPage('GET', '/configuracoes');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Configuracoes do tenant', $response['body']);
        self::assertStringContainsString('<option value="BRL" selected>', $response['body']);
        self::assertStringContainsString('<option value="America/Sao_Paulo" selected>', $response['body']);
        self::assertStringContainsString('<option value="d/m/Y" selected>', $response['body']);
    }

    public function testSettingsPageShowsTheOtherTenantSettingsForItsOwnUser(): void
    {
        $this->loginAs('admin2@example.com');

        $response = $this->dispatchPage('GET', '/configuracoes');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('<option value="USD" selected>', $response['body']);
        self::assertStringContainsString('<option value="America/New_York" selected>', $response['body']);
        self::assertStringNotContainsString('<option value="BRL" selected>', $response['body']);
    }

    public function testUpdateSettingsPersistsForCurrentTenant(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchUserForm('POST', '/configuracoes', [
            'currency' => 'USD',
            'timezone' => 'America/New_York',
            'language' => 'en-US',
            'date_format' => 'm/d/Y',
        ]);

        self::assertSame(302, $response['status']);
        self::assertSame('/configuracoes', $response['headers']['Location']);

        $row = $this->fetchOne('SELECT currency, timezone, language, date_format FROM tenant_settings WHERE tenant_id = :id', ['id' => 1]);
        self::assertSame('USD', $row['currency']);
        self::assertSame('America/New_York', $row['timezone']);
        self::assertSame('en-US', $row['language']);
        self::assertSame('m/d/Y', $row['date_format']);

        // O outro tenant nao pode ter sido afetado.
        $other = $this->fetchOne('SELECT currency FROM tenant_settings WHERE tenant_id = :id', ['id' => 2]);
        self::assertSame('USD', $other['currency'], 'Tenant 2 ja era USD; o valor nao pode ter vindo do tenant 1.');
    }

    public function testUpdateSettingsDoesNotTouchAnotherTenant(): void
    {
        $this->loginAs('admin2@example.com');

        $this->dispatchUserForm('POST', '/configuracoes', [
            'currency' => 'EUR',
            'timezone' => 'Europe/Lisbon',
            'language' => 'es-ES',
            'date_format' => 'Y-m-d',
        ]);

        self::assertSame('BRL', $this->fetchOne('SELECT currency FROM tenant_settings WHERE tenant_id = :id', ['id' => 1])['currency']);
        self::assertSame('EUR', $this->fetchOne('SELECT currency FROM tenant_settings WHERE tenant_id = :id', ['id' => 2])['currency']);
    }

    public function testUpdateSettingsRejectsInvalidCurrencyWithoutWriting(): void
    {
        $this->loginAs('admin1@example.com');

        $response = $this->dispatchUserForm('POST', '/configuracoes', [
            'currency' => 'REAL',
            'timezone' => 'America/Sao_Paulo',
            'language' => 'pt-BR',
            'date_format' => 'd/m/Y',
        ]);

        self::assertSame(302, $response['status'], 'Erro de regra volta para a tela (PRG), nao 500.');
        self::assertSame('BRL', $this->fetchOne('SELECT currency FROM tenant_settings WHERE tenant_id = :id', ['id' => 1])['currency']);
    }

    public function testUpdateSettingsRejectsInvalidTimezone(): void
    {
        $this->loginAs('admin1@example.com');

        $this->dispatchUserForm('POST', '/configuracoes', [
            'currency' => 'BRL',
            'timezone' => 'Mars/Phobos',
            'language' => 'pt-BR',
            'date_format' => 'd/m/Y',
        ]);

        self::assertSame('America/Sao_Paulo', $this->fetchOne('SELECT timezone FROM tenant_settings WHERE tenant_id = :id', ['id' => 1])['timezone']);
    }

    public function testUpdateSettingsRejectsUnsafeDateFormat(): void
    {
        $this->loginAs('admin1@example.com');

        foreach (['d/m/Y\\', 'U', 'r'] as $format) {
            $this->dispatchUserForm('POST', '/configuracoes', [
                'currency' => 'BRL',
                'timezone' => 'America/Sao_Paulo',
                'language' => 'pt-BR',
                'date_format' => $format,
            ]);

            self::assertSame(
                'd/m/Y',
                $this->fetchOne('SELECT date_format FROM tenant_settings WHERE tenant_id = :id', ['id' => 1])['date_format'],
                'Formato ' . $format . ' nao pode ser gravado.',
            );
        }
    }

    public function testRejectedUpdateShowsErrorMessage(): void
    {
        $this->loginAs('admin1@example.com');

        $this->dispatchUserForm('POST', '/configuracoes', [
            'currency' => 'X',
            'timezone' => 'America/Sao_Paulo',
            'language' => 'pt-BR',
            'date_format' => 'd/m/Y',
        ]);

        $page = $this->dispatchPage('GET', '/configuracoes');
        self::assertStringContainsString('Moeda invalida', $page['body']);
    }

    public function testLoginLoadsTenantSettingsIntoTheSession(): void
    {
        $this->loginAs('admin2@example.com');

        $auth = $_SESSION['auth'] ?? [];
        self::assertSame('USD', $auth['settings']['currency'] ?? null);
        self::assertSame('America/New_York', $auth['settings']['timezone'] ?? null);
    }

    public function testMissingSettingsRowIsCreatedOnDemand(): void
    {
        $this->execSql('DELETE FROM tenant_settings WHERE tenant_id = :id', ['id' => 1]);

        $this->loginAs('admin1@example.com');

        $row = $this->fetchOne('SELECT currency, timezone FROM tenant_settings WHERE tenant_id = :id', ['id' => 1]);
        self::assertNotNull($row, 'O login deve recriar as configuracoes padrao.');
        self::assertSame('BRL', $row['currency']);
        self::assertSame('America/Sao_Paulo', $row['timezone']);
    }

    public function testTenantContextFallsBackToDefaultsWithoutSettingsInSession(): void
    {
        $context = new TenantContext(new Session());
        $_SESSION['auth'] = ['user_id' => 1, 'tenant_id' => 1, 'permissions' => []];

        self::assertSame('BRL', $context->currency());
        self::assertSame('America/Sao_Paulo', $context->timezone());
        self::assertSame('pt-BR', $context->language());
        self::assertSame('d/m/Y', $context->dateFormat());
    }

    public function testUserListFormatsLastLoginWithTenantDateFormat(): void
    {
        $this->loginAs('admin1@example.com');

        // Depois do login: o proprio login grava last_login.
        $this->execSql(
            'UPDATE users SET last_login = :value WHERE id = :id',
            ['value' => '2026-09-27 14:30:00', 'id' => 1],
        );

        $page = $this->dispatchPage('GET', '/usuarios');

        self::assertStringContainsString('27/09/2026 14:30', $page['body'], 'Tenant 1 usa d/m/Y no fuso de Sao Paulo.');
    }

    public function testUserListAppliesTenantTimezoneAndFormat(): void
    {
        $this->loginAs('admin2@example.com');

        $this->execSql(
            'UPDATE users SET last_login = :value WHERE id = :id',
            ['value' => '2026-09-27 14:30:00', 'id' => 3],
        );

        $page = $this->dispatchPage('GET', '/usuarios');

        // 14:30 em America/Sao_Paulo (-03) = 13:30 em America/New_York (EDT, -04).
        self::assertStringContainsString('09/27/2026 13:30', $page['body'], 'Tenant 2 usa m/d/Y e converte o fuso.');
    }

    public function testTenantRepositoryCreateSeedsDefaultSettings(): void
    {
        $this->bootApplication();

        $repository = new TenantRepository();
        $tenantId = $repository->create(
            ['name' => 'Probe Tenant', 'document' => '999', 'email' => 'probe@example.com'],
        );

        $settings = $this->fetchOne('SELECT currency, timezone, language, date_format FROM tenant_settings WHERE tenant_id = :id', ['id' => $tenantId]);
        self::assertSame('BRL', $settings['currency']);
        self::assertSame('America/Sao_Paulo', $settings['timezone']);
        self::assertSame('pt-BR', $settings['language']);
        self::assertSame('d/m/Y', $settings['date_format']);

        $this->execSql('DELETE FROM tenant_settings WHERE tenant_id = :id', ['id' => $tenantId]);
        $this->execSql('DELETE FROM tenants WHERE id = :id', ['id' => $tenantId]);
    }

    public function testApplicationTimeZoneIsNotChangedByTenantSettings(): void
    {
        $this->loginAs('admin2@example.com');
        $this->dispatchPage('GET', '/configuracoes');

        // O fuso do tenant e aplicado so na exibicao: a escrita continua no
        // fuso global, para nao deslocar timestamps ja gravados.
        self::assertSame('America/Sao_Paulo', date_default_timezone_get());
    }

    private function loginAs(string $email): void
    {
        $_SESSION = [];

        $response = $this->dispatchJson('POST', '/api/v1/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);

        self::assertSame(200, $response['status'], 'Login falhou para ' . $email);
    }
}
