# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 48 — Especificação do Banco de Dados Relacional Completo

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Modelo relacional, estrutura de tabelas, relacionamentos, normalização, índices e arquitetura SaaS multiempresa

---

# 1. Objetivo

O banco de dados do ImportControl deverá ser projetado para suportar:


Pequenos negócios

Crescimento progressivo

Múltiplos usuários

Múltiplas empresas

Grande volume de dados

Manutenção simplificada


---

# 2. Banco de Dados

Tecnologia:


MariaDB 10+

ou

MySQL 8+


Configuração:


Charset:

utf8mb4

Collation:

utf8mb4_unicode_ci


---

# 3. Estratégia SaaS Multiempresa

O sistema deverá ser preparado para múltiplos clientes.

Modelo:


Desenvolvedor

    ↓

Empresas (Tenants)

    ↓

Usuários

    ↓

Dados operacionais


---

# 4. Conceito Tenant

Cada empresa cadastrada será um tenant.

Exemplo:


Empresa A

Produtos A

Vendas A

Financeiro A

Empresa B

Produtos B

Vendas B

Financeiro B


---

# 5. Regra Multi-Tenant

Todas tabelas operacionais deverão possuir:

```sql
tenant_id BIGINT NOT NULL

Exemplo:

products

id

tenant_id

name
6. Diagrama Geral de Entidades

Relacionamento principal:

TENANTS

  |

  |

USERS

  |

  |

PRODUCTS

  |

  |

STOCK

  |

  |

SALES

  |

  |

FINANCIAL

  |

  |

REPORTS
7. Tabela tenants

Representa empresas cadastradas.

CREATE TABLE tenants (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(150) NOT NULL,

document VARCHAR(30),

email VARCHAR(150),

phone VARCHAR(30),

status ENUM(
'ACTIVE',
'INACTIVE'
)
DEFAULT 'ACTIVE',

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

updated_at TIMESTAMP NULL

);
8. Tabela users

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

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

FOREIGN KEY
(tenant_id)
REFERENCES tenants(id)

);
9. Tabela roles

Perfis de acesso.

CREATE TABLE roles (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

description TEXT

);
10. Tabela permissions

Permissões do sistema.

CREATE TABLE permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

description TEXT

);
11. Tabela role_permissions

Relacionamento:

Perfil

↓

Permissões
CREATE TABLE role_permissions (

role_id BIGINT,

permission_id BIGINT,

PRIMARY KEY(
role_id,
permission_id
)

);
12. Tabela user_logs

Auditoria de usuários.

CREATE TABLE user_logs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

user_id BIGINT,

action VARCHAR(100),

ip VARCHAR(50),

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
13. Módulo Produtos
products

Tabela principal de produtos.

CREATE TABLE products (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

category_id BIGINT,

name VARCHAR(200),

sku VARCHAR(100),

barcode VARCHAR(100),

description TEXT,

cost_price DECIMAL(12,2),

sale_price DECIMAL(12,2),

minimum_price DECIMAL(12,2),

stock_minimum INT DEFAULT 0,

status ENUM(
'ACTIVE',
'INACTIVE'
),

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
14. Categorias de Produtos
CREATE TABLE product_categories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

description TEXT

);
15. Marcas
CREATE TABLE brands (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100)

);
16. Produtos e Moedas

Permitir custos internacionais.

CREATE TABLE currencies (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

code VARCHAR(10),

symbol VARCHAR(10),

description VARCHAR(50)

);

Exemplo:

USD

BRL

EUR
17. Histórico de Custos

Guardar variações.

CREATE TABLE product_cost_history (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

product_id BIGINT,

currency_id BIGINT,

cost_value DECIMAL(12,2),

exchange_rate DECIMAL(12,4),

converted_value DECIMAL(12,2),

created_at TIMESTAMP

);
18. Módulo Estoque
warehouses

Locais físicos.

CREATE TABLE warehouses (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

address TEXT,

status ENUM(
'ACTIVE',
'INACTIVE'
)

);
19. Estoque Atual
CREATE TABLE warehouse_stock (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

warehouse_id BIGINT,

product_id BIGINT,

quantity INT DEFAULT 0

);
20. Movimentação Estoque
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

created_at TIMESTAMP

);
21. Módulo Clientes
customers
CREATE TABLE customers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

document VARCHAR(30),

phone VARCHAR(30),

email VARCHAR(150),

address TEXT,

city VARCHAR(100),

state VARCHAR(50),

created_at TIMESTAMP

);
22. Módulo Vendas
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

cost_total DECIMAL(12,2),

profit DECIMAL(12,2),

status VARCHAR(30),

created_at TIMESTAMP

);
23. Itens Venda
CREATE TABLE sale_items (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

product_id BIGINT,

quantity INT,

cost_price DECIMAL(12,2),

sale_price DECIMAL(12,2),

discount DECIMAL(12,2),

subtotal DECIMAL(12,2)

);
24. Pagamentos
CREATE TABLE payments (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

method VARCHAR(50),

amount DECIMAL(12,2),

installment INT,

due_date DATE,

payment_date DATE,

status VARCHAR(30)

);
25. Módulo Compras Internacionais
imports
CREATE TABLE imports (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

country VARCHAR(100),

trip_date DATE,

currency_id BIGINT,

exchange_rate DECIMAL(12,4),

total_cost DECIMAL(12,2),

status VARCHAR(30)

);
26. Produtos Importados
CREATE TABLE import_items (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

import_id BIGINT,

product_id BIGINT,

quantity INT,

unit_cost DECIMAL(12,2),

total_cost DECIMAL(12,2)

);
27. Despesas de Viagem
CREATE TABLE travel_expenses (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

import_id BIGINT,

category VARCHAR(100),

currency_id BIGINT,

amount DECIMAL(12,2),

exchange_rate DECIMAL(12,4),

converted_amount DECIMAL(12,2)

);
28. Módulo Financeiro
financial_accounts
CREATE TABLE financial_accounts (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

type VARCHAR(50),

balance DECIMAL(12,2)

);
29. Categorias Financeiras
CREATE TABLE financial_categories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

type VARCHAR(20)

);
30. Transações Financeiras
CREATE TABLE financial_transactions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

account_id BIGINT,

category_id BIGINT,

type VARCHAR(30),

description TEXT,

amount DECIMAL(12,2),

reference_type VARCHAR(50),

reference_id BIGINT,

created_at TIMESTAMP

);
31. Contas a Receber
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
CREATE TABLE accounts_payable (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

supplier_id BIGINT,

category_id BIGINT,

amount DECIMAL(12,2),

due_date DATE,

status VARCHAR(30)

);
33. Fornecedores
CREATE TABLE suppliers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

document VARCHAR(30),

phone VARCHAR(30)

);
34. Sistema de Notificações
CREATE TABLE notifications (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

type VARCHAR(30),

message TEXT,

read_at DATETIME,

created_at TIMESTAMP

);
35. Metas
CREATE TABLE business_goals (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

type VARCHAR(50),

target_value DECIMAL(12,2),

period VARCHAR(20)

);
36. Índices Importantes

Criar índices:

INDEX tenant_id

INDEX created_at

INDEX product_id

INDEX customer_id

INDEX sale_id

INDEX status
37. Regras de Integridade

Utilizar:

Foreign Keys

Constraints

Unique Index

Not Null

Enums controlados
38. Exclusão de Dados

Preferir:

Soft Delete

Adicionar:

deleted_at DATETIME NULL

Nunca apagar dados financeiros definitivamente.

39. Backup

Estratégia:

Backup diário

Backup semanal completo

Retenção configurável
40. Segurança Banco

Aplicar:

Usuário banco limitado

Senha forte

Acesso remoto restrito

Logs
41. Performance

Aplicar:

Índices

Paginação

Consultas otimizadas

Views para relatórios

Cache
42. Relacionamentos Principais
Tenant

1:N

Users


Tenant

1:N

Products


Product

1:N

Stock Movements


Customer

1:N

Sales


Sale

1:N

Sale Items


Sale

1:N

Payments


Import

1:N

Import Items


Account

1:N

Financial Transactions
43. Critérios de Aceitação
[ ] Banco normalizado

[ ] Multiempresa preparado

[ ] Relacionamentos definidos

[ ] Índices criados

[ ] Auditoria suportada

[ ] Moedas suportadas

[ ] Estoque integrado

[ ] Financeiro integrado

[ ] Vendas integradas

[ ] Backup planejado

[ ] Estrutura escalável
Encerramento da Parte 48

O banco de dados do ImportControl foi projetado para suportar uma evolução de pequeno sistema administrativo para uma plataforma SaaS completa.

A estrutura garante:

Organização dos dados

Separação entre empresas

Segurança

Alta manutenção

Escalabilidade

Integração entre módulos

A próxima etapa será detalhar a implementação prática do banco:

migrations;
criação das tabelas;
seeds iniciais;
padrões de versionamento;
comandos de instalação.