# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 60 — Especificação do Banco de Dados Completo, Modelo Relacional e Diagrama de Entidades

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Arquitetura do banco de dados, entidades, relacionamentos, integridade, índices e modelo relacional

---

# 1. Objetivo

Este documento define a estrutura oficial do banco de dados do ImportControl.

O banco deverá ser desenvolvido seguindo princípios de:


Modelo relacional

Integridade referencial

Alta performance

Facilidade manutenção

Escalabilidade SaaS

Separação por tenant

Segurança dos dados


---

# 2. Banco de Dados

Tecnologia:


MariaDB 10+

MySQL 8+


Charset:


utf8mb4


Collation:


utf8mb4_unicode_ci


---

# 3. Convenções do Banco

Padrões obrigatórios:


Nome tabelas:

snake_case

Nome campos:

snake_case

Primary Key:

id

Foreign Key:

nome_tabela_id

Datas:

created_at

updated_at


---

# 4. Estrutura Multi-Tenant

Todas entidades comerciais deverão possuir:

```sql
tenant_id BIGINT

Objetivo:

Isolamento dos dados

Controle SaaS

Segurança empresarial
5. Diagrama Geral de Entidades

Modelo simplificado:

COMPANIES

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
6. Módulo Empresas (SaaS)
Tabela companies

Responsável pelas empresas clientes.

CREATE TABLE companies (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(150) NOT NULL,

document VARCHAR(30),

email VARCHAR(150),

phone VARCHAR(30),

logo VARCHAR(255),

status VARCHAR(30),

created_at TIMESTAMP,

updated_at TIMESTAMP

);

Relacionamentos:

companies

1:N

users
7. Configurações da Empresa
Tabela tenant_settings
CREATE TABLE tenant_settings (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT NOT NULL,

currency VARCHAR(10),

timezone VARCHAR(50),

language VARCHAR(10),

date_format VARCHAR(20),

created_at TIMESTAMP,

updated_at TIMESTAMP,

FOREIGN KEY
(tenant_id)

REFERENCES companies(id)

);
8. Usuários
Tabela users
CREATE TABLE users (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

email VARCHAR(150),

password VARCHAR(255),

role_id BIGINT,

status VARCHAR(30),

last_login DATETIME,

created_at TIMESTAMP,

updated_at TIMESTAMP

);

Relacionamentos:

companies

1:N

users


roles

1:N

users
9. Perfis de Usuário
Tabela roles
CREATE TABLE roles (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

description TEXT,

created_at TIMESTAMP

);

Exemplos:

Administrador

Vendedor

Financeiro

Estoque
10. Permissões
Tabela permissions
CREATE TABLE permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

module VARCHAR(100),

description TEXT

);
Relação perfil/permissão

Tabela:

role_permissions
CREATE TABLE role_permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

role_id BIGINT,

permission_id BIGINT

);
11. Clientes

Módulo CRM.

Tabela customers
CREATE TABLE customers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

document VARCHAR(30),

phone VARCHAR(30),

email VARCHAR(150),

address TEXT,

notes TEXT,

status VARCHAR(30),

created_at TIMESTAMP,

updated_at TIMESTAMP

);

Relacionamento:

customers

1:N

sales
12. Fornecedores
Tabela suppliers
CREATE TABLE suppliers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

document VARCHAR(30),

phone VARCHAR(30),

email VARCHAR(150),

created_at TIMESTAMP,

updated_at TIMESTAMP

);
13. Categorias de Produtos
Tabela categories
CREATE TABLE categories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

description TEXT,

status VARCHAR(30),

created_at TIMESTAMP

);

Relacionamento:

categories

1:N

products
14. Produtos
Tabela products
CREATE TABLE products (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

category_id BIGINT,

supplier_id BIGINT,

sku VARCHAR(100),

barcode VARCHAR(100),

name VARCHAR(150),

description TEXT,

cost_price DECIMAL(12,2),

sale_price DECIMAL(12,2),

minimum_price DECIMAL(12,2),

stock_quantity DECIMAL(12,3),

status VARCHAR(30),

created_at TIMESTAMP,

updated_at TIMESTAMP

);
15. Controle de Custos

Tabela:

product_cost_history
CREATE TABLE product_cost_history (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

product_id BIGINT,

old_cost DECIMAL(12,2),

new_cost DECIMAL(12,2),

reason TEXT,

user_id BIGINT,

created_at TIMESTAMP

);

Objetivo:

Histórico de alteração custo
16. Importações
Tabela imports

Representa viagens e compras internacionais.

CREATE TABLE imports (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

description VARCHAR(150),

country VARCHAR(100),

start_date DATE,

end_date DATE,

currency VARCHAR(10),

exchange_rate DECIMAL(10,4),

total_cost DECIMAL(12,2),

status VARCHAR(30),

created_at TIMESTAMP

);
17. Despesas da Importação
Tabela import_expenses
CREATE TABLE import_expenses (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

import_id BIGINT,

description VARCHAR(150),

currency VARCHAR(10),

amount DECIMAL(12,2),

exchange_rate DECIMAL(10,4),

converted_amount DECIMAL(12,2),

category VARCHAR(100),

created_at TIMESTAMP

);

Exemplos:

Passagem

Hotel

Alimentação

Transporte
18. Produtos Importados
Tabela import_products
CREATE TABLE import_products (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

import_id BIGINT,

product_id BIGINT,

quantity DECIMAL(12,3),

unit_cost DECIMAL(12,2),

currency VARCHAR(10),

exchange_rate DECIMAL(10,4),

real_cost DECIMAL(12,2)

);
19. Estoque
Tabela stock_movements
CREATE TABLE stock_movements (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

product_id BIGINT,

type VARCHAR(30),

quantity DECIMAL(12,3),

reference_type VARCHAR(50),

reference_id BIGINT,

created_at TIMESTAMP

);

Tipos:

ENTRY

SALE

ADJUSTMENT

RETURN
20. Vendas
Tabela sales
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

cost_total DECIMAL(12,2),

profit DECIMAL(12,2),

sale_date DATETIME,

created_at TIMESTAMP

);
21. Itens da Venda
Tabela sale_items
CREATE TABLE sale_items (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

product_id BIGINT,

quantity DECIMAL(12,3),

cost_price DECIMAL(12,2),

sale_price DECIMAL(12,2),

discount DECIMAL(12,2),

subtotal DECIMAL(12,2)

);
22. Pagamentos
Tabela payments
CREATE TABLE payments (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

method VARCHAR(50),

amount DECIMAL(12,2),

installments INT,

status VARCHAR(30),

payment_date DATETIME

);
23. Financeiro
Tabela financial_transactions
CREATE TABLE financial_transactions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

category_id BIGINT,

type VARCHAR(30),

description VARCHAR(255),

amount DECIMAL(12,2),

transaction_date DATE,

reference_type VARCHAR(50),

reference_id BIGINT,

status VARCHAR(30),

created_at TIMESTAMP

);
24. Categorias Financeiras
financial_categories
CREATE TABLE financial_categories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

type VARCHAR(30),

name VARCHAR(100),

description TEXT,

created_at TIMESTAMP

);
25. Contas a Receber
accounts_receivable
CREATE TABLE accounts_receivable (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

customer_id BIGINT,

sale_id BIGINT,

amount DECIMAL(12,2),

due_date DATE,

payment_date DATE,

status VARCHAR(30)

);
26. Contas a Pagar
accounts_payable
CREATE TABLE accounts_payable (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

supplier_id BIGINT,

description VARCHAR(255),

amount DECIMAL(12,2),

due_date DATE,

payment_date DATE,

status VARCHAR(30)

);
27. Auditoria
audit_logs
CREATE TABLE audit_logs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

module VARCHAR(100),

action VARCHAR(100),

description TEXT,

ip_address VARCHAR(50),

created_at TIMESTAMP

);
28. Dashboard
dashboard_metrics
CREATE TABLE dashboard_metrics (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

metric_name VARCHAR(100),

metric_value DECIMAL(15,2),

reference_date DATE,

created_at TIMESTAMP

);
29. Índices Obrigatórios

Criar índices:

INDEX tenant_id

INDEX created_at

INDEX status

INDEX product_id

INDEX customer_id

INDEX sale_date
30. Integridade Referencial

Todas relações devem utilizar:

FOREIGN KEY

ON DELETE RESTRICT

ON UPDATE CASCADE
31. Soft Delete

Dados importantes não devem ser apagados.

Adicionar:

deleted_at

Em:

products

customers

users

sales
32. Auditoria Financeira

Nunca excluir:

Vendas

Movimentações financeiras

Pagamentos

Importações

Apenas:

Cancelar

Estornar

Inativar
33. Views SQL Futuras

Criar:

vw_sales_profit

vw_stock_position

vw_financial_summary

vw_customer_ranking
34. Procedures Futuras

Preparar:

Atualizar estoque

Calcular lucro

Gerar indicadores

Fechamento mensal
35. Backup

Estratégia:

Backup diário completo

Backup incremental

Retenção 30 dias
36. Critérios de Aceitação
[ ] Todas tabelas criadas

[ ] Relacionamentos funcionando

[ ] Multi-tenant aplicado

[ ] Índices criados

[ ] Integridade configurada

[ ] Auditoria implementada

[ ] Banco documentado

[ ] Migrations criadas
Encerramento da Parte 60

O modelo de dados definido nesta etapa representa a base estrutural do ImportControl.

A arquitetura permitirá controlar:

Empresas

Usuários

Produtos

Importações

Estoque

Clientes

Vendas

Financeiro

Indicadores

com segurança e capacidade de crescimento.

O banco está preparado para evoluir de uma aplicação individual para uma plataforma SaaS comercial.