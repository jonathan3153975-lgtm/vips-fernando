<?php

declare(strict_types=1);

use App\Database\Migration;

return new class extends Migration {
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                parent_id BIGINT UNSIGNED NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT "ACTIVE",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_categories_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
                UNIQUE KEY uq_categories_tenant_name (tenant_id, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE brands (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_brands_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                UNIQUE KEY uq_brands_tenant_name (tenant_id, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE products (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                category_id BIGINT UNSIGNED NULL,
                brand_id BIGINT UNSIGNED NULL,
                supplier_id BIGINT UNSIGNED NULL,
                default_import_item_id BIGINT UNSIGNED NULL,
                sku VARCHAR(100) NOT NULL,
                barcode VARCHAR(100) NULL,
                name VARCHAR(150) NOT NULL,
                description TEXT NULL,
                unit VARCHAR(20) NOT NULL DEFAULT "UN",
                status VARCHAR(30) NOT NULL DEFAULT "ACTIVE",
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_products_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
                CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
                UNIQUE KEY uq_products_tenant_sku (tenant_id, sku),
                UNIQUE KEY uq_products_tenant_barcode (tenant_id, barcode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE product_prices (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id BIGINT UNSIGNED NOT NULL,
                cost_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                sale_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                minimum_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                margin DECIMAL(7,2) NOT NULL DEFAULT 0.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_product_prices_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY uq_product_prices_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE stock (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000,
                reserved_quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000,
                minimum_quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_stock_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY uq_stock_tenant_product (tenant_id, product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );

        $pdo->exec(
            'CREATE TABLE stock_movements (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                import_item_id BIGINT UNSIGNED NULL,
                sale_item_id BIGINT UNSIGNED NULL,
                user_id BIGINT UNSIGNED NULL,
                type VARCHAR(30) NOT NULL,
                quantity DECIMAL(12,3) NOT NULL,
                balance_after DECIMAL(12,3) NOT NULL,
                reference_type VARCHAR(50) NOT NULL,
                reference_id BIGINT UNSIGNED NULL,
                notes VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_stock_movements_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                CONSTRAINT fk_stock_movements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                KEY idx_stock_movements_product (product_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;'
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS stock_movements');
        $pdo->exec('DROP TABLE IF EXISTS stock');
        $pdo->exec('DROP TABLE IF EXISTS product_prices');
        $pdo->exec('DROP TABLE IF EXISTS products');
        $pdo->exec('DROP TABLE IF EXISTS brands');
        $pdo->exec('DROP TABLE IF EXISTS categories');
    }
};
