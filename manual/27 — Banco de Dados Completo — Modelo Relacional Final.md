# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 27 — Banco de Dados Completo — Modelo Relacional Final

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Arquitetura do banco de dados relacional, entidades, relacionamentos e estrutura SQL inicial

---

# 1. Objetivo

Esta parte define a estrutura completa do banco de dados do ImportControl.

O banco deverá ser:

- relacional;
- normalizado;
- seguro;
- preparado para SaaS;
- escalável;
- fácil de manter;
- compatível com MariaDB/MySQL.

---

# 2. Princípios do Banco de Dados

O banco seguirá:


Normalização até 3FN

Integridade referencial

Foreign Keys

Índices estratégicos

Auditoria

Soft Delete

Multi-Tenant


---

# 3. Convenções

## Nome das tabelas

Utilizar:


snake_case


Exemplo:


product_categories
sales_items
financial_transactions


---

# 4. Padrão dos Campos

Toda tabela principal deverá possuir:

```sql
id

tenant_id

created_at

updated_at

deleted_at

Exemplo:

products

id
tenant_id
name
created_at
updated_at
deleted_at
5. Motor do Banco

Recomendado:

ENGINE=InnoDB

Charset:

utf8mb4

Collation:

utf8mb4_unicode_ci
6. Diagrama Geral de Relacionamentos

Visão macro:

tenants

  |

  |---- users

  |

  |---- products

  |

  |---- customers

  |

  |---- trips

  |

  |---- sales

  |

  |---- financial_transactions

  |

  |---- reports
7. Tabela Empresas (SaaS)
tenants

Responsável pelas empresas cadastradas.

CREATE TABLE tenants (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(150) NOT NULL,

document VARCHAR(30),

email VARCHAR(150),

phone VARCHAR(30),

status ENUM(
'ACTIVE',
'INACTIVE',
'BLOCKED'
)
DEFAULT 'ACTIVE',

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
8. Usuários
users

Usuários do sistema.

CREATE TABLE users (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

email VARCHAR(150),

password VARCHAR(255),

role_id BIGINT,

status ENUM(
'ACTIVE',
'INACTIVE'
),

last_login DATETIME,

created_at TIMESTAMP,

updated_at TIMESTAMP,

FOREIGN KEY
(tenant_id)
REFERENCES tenants(id)

);
9. Perfis
roles
CREATE TABLE roles (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

description TEXT

);

Exemplos:

Developer

Administrator

User

Seller
10. Permissões
permissions
CREATE TABLE permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

description TEXT

);

Exemplo:

CREATE_PRODUCT

DELETE_USER

VIEW_FINANCE
11. Relação Perfil x Permissão
role_permissions
CREATE TABLE role_permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

role_id BIGINT,

permission_id BIGINT,

FOREIGN KEY(role_id)
REFERENCES roles(id),

FOREIGN KEY(permission_id)
REFERENCES permissions(id)

);
12. Moedas
currencies

Controla moedas utilizadas.

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
13. Histórico Cambial
exchange_rates
CREATE TABLE exchange_rates (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

currency_id BIGINT,

rate DECIMAL(10,4),

date DATE,

created_at TIMESTAMP

);
14. Categorias de Produto
product_categories
CREATE TABLE product_categories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

description TEXT,

status BOOLEAN DEFAULT TRUE,

created_at TIMESTAMP

);
15. Fornecedores
suppliers
CREATE TABLE suppliers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

country VARCHAR(100),

city VARCHAR(100),

phone VARCHAR(30),

email VARCHAR(150),

created_at TIMESTAMP

);
16. Produtos
products
CREATE TABLE products (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

category_id BIGINT,

supplier_id BIGINT,

sku VARCHAR(50),

barcode VARCHAR(100),

name VARCHAR(150),

description TEXT,

image VARCHAR(255),

cost_price DECIMAL(12,2),

sale_price DECIMAL(12,2),

minimum_stock INT DEFAULT 0,

status BOOLEAN DEFAULT TRUE,

created_at TIMESTAMP,

updated_at TIMESTAMP,

deleted_at TIMESTAMP NULL

);
17. Histórico de Custos
product_cost_history
CREATE TABLE product_cost_history (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

product_id BIGINT,

old_cost DECIMAL(12,2),

new_cost DECIMAL(12,2),

reason TEXT,

user_id BIGINT,

created_at TIMESTAMP

);
18. Estoque
stock
CREATE TABLE stock (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

product_id BIGINT,

quantity INT DEFAULT 0,

minimum_quantity INT DEFAULT 0,

updated_at TIMESTAMP

);
19. Movimentação Estoque
stock_movements
CREATE TABLE stock_movements (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

product_id BIGINT,

type VARCHAR(30),

quantity INT,

reference_type VARCHAR(50),

reference_id BIGINT,

user_id BIGINT,

created_at TIMESTAMP

);

Tipos:

ENTRY

SALE

RETURN

LOSS

ADJUSTMENT
20. Clientes
customers
CREATE TABLE customers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

type ENUM(
'PF',
'PJ'
),

name VARCHAR(150),

document VARCHAR(30),

phone VARCHAR(30),

email VARCHAR(150),

address TEXT,

city VARCHAR(100),

state VARCHAR(50),

notes TEXT,

created_at TIMESTAMP,

deleted_at TIMESTAMP NULL

);
21. Viagens
trips
CREATE TABLE trips (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

country VARCHAR(100),

city VARCHAR(100),

start_date DATE,

end_date DATE,

currency_id BIGINT,

exchange_rate DECIMAL(10,4),

status VARCHAR(30),

notes TEXT,

created_at TIMESTAMP

);
22. Categorias de Despesas
expense_categories
CREATE TABLE expense_categories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

type VARCHAR(30)

);
23. Despesas de Viagem
trip_expenses
CREATE TABLE trip_expenses (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

trip_id BIGINT,

category_id BIGINT,

description TEXT,

currency_id BIGINT,

amount DECIMAL(12,2),

exchange_rate DECIMAL(10,4),

converted_amount DECIMAL(12,2),

expense_date DATE,

attachment VARCHAR(255),

created_at TIMESTAMP

);
24. Compras Internacionais
purchases
CREATE TABLE purchases (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

trip_id BIGINT,

supplier_id BIGINT,

currency_id BIGINT,

total_amount DECIMAL(12,2),

exchange_rate DECIMAL(10,4),

status VARCHAR(30),

created_at TIMESTAMP

);
25. Itens Compra
purchase_items
CREATE TABLE purchase_items (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

purchase_id BIGINT,

product_id BIGINT,

quantity INT,

unit_price DECIMAL(12,2),

total DECIMAL(12,2)

);
26. Vendas
sales
CREATE TABLE sales (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

customer_id BIGINT,

user_id BIGINT,

sale_number VARCHAR(50),

subtotal DECIMAL(12,2),

discount DECIMAL(12,2),

total DECIMAL(12,2),

profit DECIMAL(12,2),

status VARCHAR(30),

created_at TIMESTAMP

);
27. Itens Venda
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
28. Pagamentos
payments
CREATE TABLE payments (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

method VARCHAR(50),

amount DECIMAL(12,2),

due_date DATE,

paid_at DATE,

status VARCHAR(30)

);
29. Contas Financeiras
financial_accounts
CREATE TABLE financial_accounts (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

type VARCHAR(30),

balance DECIMAL(12,2)

);
30. Movimentações Financeiras
financial_transactions
CREATE TABLE financial_transactions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

type VARCHAR(20),

category_id BIGINT,

description TEXT,

amount DECIMAL(12,2),

account_id BIGINT,

reference_type VARCHAR(50),

reference_id BIGINT,

transaction_date DATE,

created_at TIMESTAMP

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

paid_at DATE,

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

paid_at DATE,

status VARCHAR(30)

);
33. Logs do Sistema
system_logs
CREATE TABLE system_logs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

action VARCHAR(100),

description TEXT,

ip VARCHAR(50),

created_at TIMESTAMP

);
34. Relatórios Agendados
reports_schedule
CREATE TABLE reports_schedule (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

report_type VARCHAR(100),

frequency VARCHAR(30),

email VARCHAR(150),

status BOOLEAN

);
35. Índices Obrigatórios

Criar índices:

INDEX tenant_id

INDEX created_at

INDEX product_id

INDEX customer_id

INDEX sale_id

INDEX trip_id
36. Regras de Integridade

Obrigatório:

Todas tabelas SaaS possuem tenant_id

Relacionamentos possuem FK

Excluir utilizando soft delete

Nunca apagar movimentações financeiras
37. Estratégia de Backup

Produção:

Backup diário

Backup semanal completo

Retenção mínima 90 dias
38. Preparação para Escala

O banco deverá suportar:

Milhares de empresas

Milhões de produtos

Milhões de vendas
39. Evolução Futura

Preparado para:

Marketplace

Aplicativo mobile

Integração bancária

IA para previsão de vendas

OCR de notas fiscais

Integração fiscal
40. Critérios de Aceitação
[ ] Banco normalizado

[ ] Multi-tenant

[ ] Foreign Keys

[ ] Índices criados

[ ] Histórico implementado

[ ] Auditoria

[ ] Estrutura SaaS

[ ] Compatível MySQL/MariaDB
Encerramento da Parte 27

O banco de dados definido nesta etapa representa a fundação do ImportControl.

A estrutura foi planejada para suportar:

Operação individual

↓

Pequena empresa

↓

Plataforma SaaS profissional

Mantendo:

organização;
segurança;
performance;
rastreabilidade;
facilidade de evolução.