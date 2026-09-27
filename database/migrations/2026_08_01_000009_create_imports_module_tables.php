<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS suppliers (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                country VARCHAR(100) NULL,
                city VARCHAR(100) NULL,
                contact_name VARCHAR(150) NULL,
                email VARCHAR(150) NULL,
                phone VARCHAR(30) NULL,
                notes TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT "ACTIVE",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_suppliers_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                UNIQUE KEY uq_suppliers_tenant_name (tenant_id, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS imports (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                responsible_user_id BIGINT UNSIGNED NULL,
                name VARCHAR(150) NOT NULL,
                description TEXT NULL,
                country VARCHAR(100) NOT NULL,
                city VARCHAR(100) NULL,
                start_date DATE NOT NULL,
                end_date DATE NULL,
                currency VARCHAR(10) NOT NULL,
                exchange_rate DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
                status VARCHAR(30) NOT NULL DEFAULT "PLANNED",
                invested_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                total_expenses DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                total_items DECIMAL(14,3) NOT NULL DEFAULT 0.000,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_imports_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_imports_user FOREIGN KEY (responsible_user_id) REFERENCES users(id) ON DELETE SET NULL,
                KEY idx_imports_tenant_status (tenant_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS exchange_rates (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                currency VARCHAR(10) NOT NULL,
                rate DECIMAL(10,4) NOT NULL,
                reference_date DATE NOT NULL,
                source VARCHAR(100) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_exchange_rates_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                UNIQUE KEY uq_exchange_rate_daily (tenant_id, currency, reference_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS import_expenses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                import_id BIGINT UNSIGNED NOT NULL,
                supplier_id BIGINT UNSIGNED NULL,
                category VARCHAR(100) NOT NULL,
                description VARCHAR(255) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                amount DECIMAL(14,2) NOT NULL,
                exchange_rate DECIMAL(10,4) NOT NULL,
                converted_amount DECIMAL(14,2) NOT NULL,
                expense_date DATE NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT "PENDING",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_import_expenses_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_import_expenses_import FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
                CONSTRAINT fk_import_expenses_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
                KEY idx_import_expenses_import (import_id, category)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS import_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                import_id BIGINT UNSIGNED NOT NULL,
                supplier_id BIGINT UNSIGNED NULL,
                product_name VARCHAR(150) NOT NULL,
                sku VARCHAR(100) NULL,
                quantity DECIMAL(12,3) NOT NULL,
                unit_cost_foreign DECIMAL(14,2) NOT NULL,
                exchange_rate DECIMAL(10,4) NOT NULL,
                unit_cost_local DECIMAL(14,2) NOT NULL,
                total_cost_local DECIMAL(14,2) NOT NULL,
                allocated_expense DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                real_unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_import_items_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_import_items_import FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
                CONSTRAINT fk_import_items_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
                KEY idx_import_items_import (import_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS import_items');
        $pdo->exec('DROP TABLE IF EXISTS import_expenses');
        $pdo->exec('DROP TABLE IF EXISTS exchange_rates');
        $pdo->exec('DROP TABLE IF EXISTS imports');
        $pdo->exec('DROP TABLE IF EXISTS suppliers');
    }
};
