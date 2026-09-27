<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS financial_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                type VARCHAR(30) NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT "ACTIVE",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_financial_categories_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                UNIQUE KEY uq_financial_categories_name (tenant_id, type, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS financial_transactions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                category_id BIGINT UNSIGNED NOT NULL,
                type VARCHAR(30) NOT NULL,
                description VARCHAR(255) NOT NULL,
                amount DECIMAL(14,2) NOT NULL,
                transaction_date DATE NOT NULL,
                payment_date DATE NULL,
                reference_type VARCHAR(50) NOT NULL,
                reference_id BIGINT UNSIGNED NULL,
                status VARCHAR(30) NOT NULL DEFAULT "PENDING",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_financial_transactions_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_financial_transactions_category FOREIGN KEY (category_id) REFERENCES financial_categories(id),
                KEY idx_financial_transactions_tenant_date (tenant_id, transaction_date),
                KEY idx_financial_transactions_reference (reference_type, reference_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS accounts_receivable (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                customer_id BIGINT UNSIGNED NULL,
                sale_id BIGINT UNSIGNED NULL,
                description VARCHAR(255) NOT NULL,
                amount DECIMAL(14,2) NOT NULL,
                due_date DATE NOT NULL,
                payment_date DATE NULL,
                status VARCHAR(30) NOT NULL DEFAULT "PENDING",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_accounts_receivable_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_accounts_receivable_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
                CONSTRAINT fk_accounts_receivable_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,
                KEY idx_accounts_receivable_due (tenant_id, due_date, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS accounts_payable (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                supplier_id BIGINT UNSIGNED NULL,
                import_id BIGINT UNSIGNED NULL,
                description VARCHAR(255) NOT NULL,
                amount DECIMAL(14,2) NOT NULL,
                due_date DATE NOT NULL,
                payment_date DATE NULL,
                status VARCHAR(30) NOT NULL DEFAULT "PENDING",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_accounts_payable_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_accounts_payable_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
                CONSTRAINT fk_accounts_payable_import FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE SET NULL,
                KEY idx_accounts_payable_due (tenant_id, due_date, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS cash_flow (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                reference_date DATE NOT NULL,
                initial_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                income_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                expense_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                final_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_cash_flow_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                UNIQUE KEY uq_cash_flow_tenant_date (tenant_id, reference_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS cash_flow');
        $pdo->exec('DROP TABLE IF EXISTS accounts_payable');
        $pdo->exec('DROP TABLE IF EXISTS accounts_receivable');
        $pdo->exec('DROP TABLE IF EXISTS financial_transactions');
        $pdo->exec('DROP TABLE IF EXISTS financial_categories');
    }
};
