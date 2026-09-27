# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 39 — Especificação do Banco de Dados Completo e Modelo Relacional

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Estrutura do banco de dados, entidades, relacionamentos, normalização e regras de integridade

---

# 1. Objetivo

Esta parte define a estrutura completa do banco de dados do ImportControl.

O banco deverá ser:


Relacional

Normalizado

Seguro

Escalável

Preparado para SaaS

Fácil manutenção

Com integridade referencial


---

# 2. Tecnologia do Banco

Utilizar:


MariaDB 10+

ou

MySQL 8+


---

# 3. Padrões do Banco

Seguir:


UTF-8 / utf8mb4

InnoDB

Foreign Keys

Índices estratégicos

Timestamps automáticos


---

# 4. Estratégia SaaS Multi Tenant

O sistema será preparado para múltiplas empresas utilizando a mesma instalação.

Todas as tabelas de negócio deverão possuir:

```sql
tenant_id

Exemplo:

Tabela:

products

Estrutura:

id

tenant_id

name

price

Regra:

Nenhum dado poderá ser acessado sem validar:

WHERE tenant_id = usuário_atual
5. Diagrama Conceitual

Relacionamento principal:

TENANT

  |
  |
 USERS

  |
  |
 ROLES

  |
  |
 PRODUCTS

  |
  |
 SALES

  |
  |
 FINANCE

  |
  |
 REPORTS
6. Módulo SaaS
6.1 tenants

Responsável pelas empresas cadastradas.

CREATE TABLE tenants (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(150) NOT NULL,

document VARCHAR(20),

email VARCHAR(150),

phone VARCHAR(30),

status ENUM(
'ACTIVE',
'INACTIVE'
)
DEFAULT 'ACTIVE',

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
ON UPDATE CURRENT_TIMESTAMP

);
7. Usuários e Permissões
7.1 users

Usuários do sistema.

CREATE TABLE users (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT NOT NULL,

name VARCHAR(150),

email VARCHAR(150),

password VARCHAR(255),

status ENUM(
'ACTIVE',
'INACTIVE'
),

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY
(tenant_id)
REFERENCES tenants(id)

);
8. Perfis de Usuário
roles
CREATE TABLE roles (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

description TEXT

);

Exemplos:

Administrador

Gerente

Vendedor
9. Relação Usuário Perfil
user_roles
CREATE TABLE user_roles (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

user_id BIGINT,

role_id BIGINT,

FOREIGN KEY(user_id)
REFERENCES users(id),

FOREIGN KEY(role_id)
REFERENCES roles(id)

);
10. Permissões
permissions
CREATE TABLE permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

module VARCHAR(100)

);

Exemplo:

products.create

sales.delete

finance.view
11. Relação Perfil Permissão
role_permissions
CREATE TABLE role_permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

role_id BIGINT,

permission_id BIGINT

);
12. Configurações Gerais
settings
CREATE TABLE settings (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

key_name VARCHAR(100),

value TEXT

);

Exemplo:

Nome empresa

Logo

Moeda padrão

Fuso horário
13. Cadastro de Moedas
currencies
CREATE TABLE currencies (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

code VARCHAR(10),

name VARCHAR(50),

symbol VARCHAR(10)

);

Exemplo:

BRL

USD

EUR
14. Cotações Cambiais
exchange_rates
CREATE TABLE exchange_rates (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

currency_id BIGINT,

rate DECIMAL(12,4),

date DATE

);

Exemplo:

USD

5.20

01/08/2026
15. Categorias de Produtos
product_categories
CREATE TABLE product_categories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

description TEXT

);
16. Fornecedores
suppliers
CREATE TABLE suppliers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

country VARCHAR(100),

email VARCHAR(150),

phone VARCHAR(30)

);
17. Produtos
products

Tabela central do sistema.

CREATE TABLE products (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

category_id BIGINT,

supplier_id BIGINT,

name VARCHAR(200),

code VARCHAR(100),

description TEXT,

cost_price DECIMAL(12,2),

sale_price DECIMAL(12,2),

currency_id BIGINT,

minimum_stock INT DEFAULT 0,

status ENUM(
'ACTIVE',
'INACTIVE'
),

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
18. Estoque
stocks
CREATE TABLE stocks (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

product_id BIGINT,

quantity INT DEFAULT 0,

reserved_quantity INT DEFAULT 0,

updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY(product_id)
REFERENCES products(id)

);
19. Movimentação Estoque
stock_movements
CREATE TABLE stock_movements (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

product_id BIGINT,

type VARCHAR(50),

quantity INT,

previous_quantity INT,

new_quantity INT,

reference_type VARCHAR(50),

reference_id BIGINT,

user_id BIGINT,

description TEXT,

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
20. Clientes
customers
CREATE TABLE customers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

cpf_cnpj VARCHAR(20),

phone VARCHAR(30),

email VARCHAR(150),

address TEXT,

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
21. Viagens
trips
CREATE TABLE trips (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

destination VARCHAR(100),

start_date DATE,

end_date DATE,

currency_id BIGINT,

exchange_rate DECIMAL(12,4),

status VARCHAR(30)

);
22. Despesas de Viagem
trip_expenses
CREATE TABLE trip_expenses (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

trip_id BIGINT,

description VARCHAR(200),

amount DECIMAL(12,2),

currency_id BIGINT,

converted_value DECIMAL(12,2)

);
23. Compras Internacionais
purchases
CREATE TABLE purchases (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

trip_id BIGINT,

supplier_id BIGINT,

purchase_date DATE,

currency_id BIGINT,

exchange_rate DECIMAL(12,4),

total_amount DECIMAL(12,2)

);
24. Itens Compra
purchase_items
CREATE TABLE purchase_items (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

purchase_id BIGINT,

product_id BIGINT,

quantity INT,

unit_cost DECIMAL(12,2),

total_cost DECIMAL(12,2)

);
25. Vendas
sales
CREATE TABLE sales (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

customer_id BIGINT,

user_id BIGINT,

sale_number VARCHAR(50),

status VARCHAR(30),

subtotal DECIMAL(12,2),

discount DECIMAL(12,2),

total DECIMAL(12,2),

profit DECIMAL(12,2),

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
26. Itens Venda
sale_items
CREATE TABLE sale_items (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

product_id BIGINT,

quantity INT,

cost_price DECIMAL(12,2),

original_price DECIMAL(12,2),

sale_price DECIMAL(12,2),

discount DECIMAL(12,2),

profit DECIMAL(12,2)

);
27. Pagamentos
payments
CREATE TABLE payments (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

method VARCHAR(50),

amount DECIMAL(12,2),

status VARCHAR(30),

payment_date DATE

);
28. Categorias Financeiras
financial_categories
CREATE TABLE financial_categories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

type ENUM(
'INCOME',
'EXPENSE'
)

);
29. Contas Financeiras
financial_accounts
CREATE TABLE financial_accounts (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

type VARCHAR(50),

balance DECIMAL(12,2)

);
30. Movimentações Financeiras
financial_transactions
CREATE TABLE financial_transactions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

type VARCHAR(30),

category_id BIGINT,

account_id BIGINT,

reference_type VARCHAR(50),

reference_id BIGINT,

description TEXT,

amount DECIMAL(12,2),

transaction_date DATE

);
31. Contas a Receber
accounts_receivable
CREATE TABLE accounts_receivable (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

customer_id BIGINT,

sale_id BIGINT,

amount DECIMAL(12,2),

due_date DATE,

status VARCHAR(30)

);
32. Contas a Pagar
accounts_payable
CREATE TABLE accounts_payable (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

supplier_id BIGINT,

description TEXT,

amount DECIMAL(12,2),

due_date DATE,

status VARCHAR(30)

);
33. Auditoria
audit_logs
CREATE TABLE audit_logs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

action VARCHAR(100),

table_name VARCHAR(100),

record_id BIGINT,

description TEXT,

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
34. Relatórios Gerados
generated_reports
CREATE TABLE generated_reports (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

name VARCHAR(150),

type VARCHAR(50),

file_path TEXT,

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
35. Índices Importantes

Criar índices:

INDEX tenant_id

INDEX product_id

INDEX customer_id

INDEX created_at

INDEX status
36. Regras de Integridade

Sempre utilizar:

ON DELETE RESTRICT

ON UPDATE CASCADE

Evitar:

Exclusão física de dados importantes

Preferir:

status = INACTIVE
37. Soft Delete

Tabelas importantes:

Adicionar:

deleted_at TIMESTAMP NULL

Aplicar em:

Produtos

Clientes

Usuários

Fornecedores
38. Backup

Implementar:

Backup diário

Backup semanal completo

Restauração testada
39. Migrações

Toda alteração deverá gerar arquivo:

Exemplo:

database/migrations

001_create_users.php

002_create_products.php

003_add_supplier.php
40. Critérios de Aceitação
[ ] Banco normalizado

[ ] Relacionamentos definidos

[ ] Foreign Keys criadas

[ ] Multi tenant funcionando

[ ] Índices configurados

[ ] Auditoria preparada

[ ] Backup planejado

[ ] Migrations estruturadas
Encerramento da Parte 39

O modelo de banco definido fornece uma base sólida para o ImportControl.

A estrutura permite:

Controle completo da operação

Separação por empresas

Crescimento SaaS

Integrações futuras

Alta manutenção

Evolução contínua

O banco foi projetado para suportar desde uma pequena operação de importação até uma plataforma comercial completa.