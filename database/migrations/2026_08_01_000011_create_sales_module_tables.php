<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS customers (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                document VARCHAR(30) NULL,
                phone VARCHAR(30) NULL,
                whatsapp VARCHAR(30) NULL,
                email VARCHAR(150) NULL,
                address TEXT NULL,
                notes TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT "ACTIVE",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_customers_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                UNIQUE KEY uq_customers_tenant_document (tenant_id, document)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS sales (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                customer_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                sale_number VARCHAR(50) NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT "OPEN",
                subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                cost_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                profit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                sale_date DATETIME NOT NULL,
                completed_at DATETIME NULL,
                cancelled_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_sales_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
                CONSTRAINT fk_sales_user FOREIGN KEY (user_id) REFERENCES users(id),
                UNIQUE KEY uq_sales_tenant_number (tenant_id, sale_number),
                KEY idx_sales_tenant_status_date (tenant_id, status, sale_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS sale_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sale_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                quantity DECIMAL(12,3) NOT NULL,
                cost_price DECIMAL(14,2) NOT NULL,
                sale_price DECIMAL(14,2) NOT NULL,
                discount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                subtotal DECIMAL(14,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id),
                KEY idx_sale_items_sale (sale_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS sale_discounts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sale_id BIGINT UNSIGNED NOT NULL,
                type VARCHAR(30) NOT NULL,
                value DECIMAL(14,2) NOT NULL,
                user_id BIGINT UNSIGNED NULL,
                reason TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_sale_discounts_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                CONSTRAINT fk_sale_discounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS payments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sale_id BIGINT UNSIGNED NOT NULL,
                method VARCHAR(50) NOT NULL,
                amount DECIMAL(14,2) NOT NULL,
                installments INT NOT NULL DEFAULT 1,
                status VARCHAR(30) NOT NULL DEFAULT "PENDING",
                payment_date DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                KEY idx_payments_sale (sale_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS sale_returns (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sale_id BIGINT UNSIGNED NOT NULL,
                customer_id BIGINT UNSIGNED NULL,
                reason TEXT NULL,
                amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                status VARCHAR(30) NOT NULL DEFAULT "OPEN",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_sale_returns_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                CONSTRAINT fk_sale_returns_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS sale_returns');
        $pdo->exec('DROP TABLE IF EXISTS payments');
        $pdo->exec('DROP TABLE IF EXISTS sale_discounts');
        $pdo->exec('DROP TABLE IF EXISTS sale_items');
        $pdo->exec('DROP TABLE IF EXISTS sales');
        $pdo->exec('DROP TABLE IF EXISTS customers');
    }
};
