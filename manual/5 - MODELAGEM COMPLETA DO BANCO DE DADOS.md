# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 5 — Modelagem Completa do Banco de Dados

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Modelo Relacional, Entidades e Estrutura de Dados

---

# 1. Objetivo

Esta seção define toda a estrutura do banco de dados do ImportControl.

O banco deverá ser:

- relacional;
- normalizado;
- escalável;
- preparado para SaaS Multi-Tenant;
- de fácil manutenção;
- seguro;
- otimizado para consultas.

O modelo deverá seguir os princípios:

- Normalização até a terceira forma normal (3FN);
- Integridade referencial;
- Chaves estrangeiras;
- Índices estratégicos;
- Auditoria;
- Soft Delete;
- Histórico de alterações.

---

# 2. Banco de Dados

## Sistema

MariaDB 11+

Compatível:

MySQL 8+

---

## Configuração

Charset:
utf8mb4


Collation:


utf8mb4_unicode_ci


Storage Engine:


InnoDB


Motivos:

- suporte a transações;
- integridade referencial;
- melhor controle de concorrência.

---

# 3. Convenções do Banco

## Tabelas

Sempre utilizar:

snake_case

Exemplo:


trip_expenses


---

## Colunas

Sempre utilizar:

snake_case

Exemplo:


created_at
updated_at
deleted_at


---

## Chaves Primárias

Todas as tabelas utilizarão:


id BIGINT UNSIGNED AUTO_INCREMENT


---

## Datas

Utilizar:


DATETIME


---

## Valores Monetários

Nunca utilizar FLOAT.

Utilizar:


DECIMAL(15,2)


Exemplo:


999999999999.99


---

# 4. Estrutura Geral de Relacionamento

Visão macro:


TENANT

├── USERS

├── TRIPS

│ ├── EXPENSES
│ ├── SUPPLIERS
│ ├── PRODUCTS
│ └── LOTS

├── CUSTOMERS

├── SALES

│ ├── SALE_ITEMS
│ └── PAYMENTS

├── FINANCE

└── REPORTS


---

# 5. Tabela: tenants

## Objetivo

Representa uma empresa cadastrada no SaaS.

---

Campos:

```sql
id

plan_id

name

slug

document

email

phone

logo

status

created_at

updated_at

deleted_at

Relacionamentos:

Tenant

1:N

Users

Trips

Products

Sales
6. Tabela: plans
Objetivo

Controla planos SaaS.

Campos:

id

name

description

price

billing_cycle

max_users

max_products

max_storage

status

created_at

updated_at

Exemplo:

Free

Starter

Professional

Enterprise
7. Tabela: subscriptions
Objetivo

Controla assinatura do cliente.

Campos:

id

tenant_id

plan_id

start_date

end_date

status

payment_status

created_at

updated_at

Status:

TRIAL

ACTIVE

PAUSED

EXPIRED

CANCELED
8. Tabela: users
Objetivo

Usuários do sistema.

Campos:

id

tenant_id

role_id

name

email

password

avatar

status

last_login

created_at

updated_at

deleted_at

Índices:

email

tenant_id

9. Tabela: roles
Objetivo

Perfis de acesso.

Campos:

id

tenant_id

name

description

created_at

Exemplos:

Administrador

Vendedor

Financeiro

Consulta
10. Tabela: permissions
Objetivo

Permissões do sistema.

Campos:

id

name

module

action

description

Exemplo:

products.create

products.delete

sales.view
11. Tabela: role_permissions

Relacionamento:

Role N:N Permission

Campos:

id

role_id

permission_id
12. Tabela: tenant_settings
Objetivo

Configurações personalizadas.

Campos:

id

tenant_id

key

value

created_at

updated_at

Exemplo:

currency = BRL

timezone = America/Sao_Paulo
13. Tabela: currencies
Objetivo

Cadastro de moedas.

Campos:

id

code

name

symbol

status

Exemplo:

USD

Dólar

$
14. Tabela: exchange_rates
Objetivo

Guardar histórico de cotações.

Campos:

id

tenant_id

currency_id

value

date

created_at

Exemplo:

USD

5.42

01/08/2026
15. Tabela: trips
Objetivo

Representa uma viagem internacional.

Campos:

id

tenant_id

name

country

city

departure_date

return_date

currency_id

exchange_rate

status

notes

created_by

created_at

updated_at

deleted_at

Status:

PLANNING

OPEN

IMPORTING

RETURNED

CLOSED

CANCELED
16. Tabela: trip_expense_categories
Objetivo

Categorias de despesas.

Campos:

id

tenant_id

name

type

created_at

Exemplo:

Hotel

Passagem

Alimentação

Transporte
17. Tabela: trip_expenses
Objetivo

Despesas vinculadas à viagem.

Campos:

id

tenant_id

trip_id

category_id

description

currency_id

amount

exchange_rate

amount_brl

is_allocatable

attachment

expense_date

created_at

updated_at

Campo importante:

is_allocatable

Define se entra no custo dos produtos.

18. Tabela: suppliers
Objetivo

Fornecedores.

Campos:

id

tenant_id

name

country

phone

email

document

notes

created_at

updated_at
19. Tabela: product_categories
Objetivo

Categorias.

Campos:

id

tenant_id

name

description

created_at
20. Tabela: products
Objetivo

Cadastro dos produtos.

Campos:

id

tenant_id

category_id

supplier_id

trip_id

sku

barcode

name

brand

description

photo

weight

created_at

updated_at

deleted_at
21. Tabela: product_lots
Objetivo

Controlar lotes de importação.

Campos:

id

tenant_id

trip_id

code

description

purchase_date

created_at

Exemplo:

MIAMI-2026-001
22. Tabela: product_purchases
Objetivo

Representar compra dos produtos.

Campos:

id

product_id

lot_id

quantity

currency_id

unit_price

exchange_rate

total_cost

created_at
23. Tabela: product_cost_allocations
Objetivo

Guardar rateios.

Campos:

id

product_id

expense_id

allocation_type

amount

created_at

Tipos:

QUANTITY

VALUE

WEIGHT

MANUAL
24. Tabela: stock_movements
Objetivo

Controle de estoque.

Campos:

id

tenant_id

product_id

type

quantity

unit_cost

reference_type

reference_id

created_by

created_at

Tipos:

ENTRY

SALE

LOSS

ADJUSTMENT

RETURN
25. Tabela: customers

Campos:

id

tenant_id

name

phone

email

document

address

notes

created_at

updated_at
26. Tabela: sales

Campos:

id

tenant_id

customer_id

sale_date

discount

shipping

total

profit

status

created_by

created_at
27. Tabela: sale_items

Campos:

id

sale_id

product_id

quantity

unit_price

unit_cost

profit

created_at
28. Tabela: payments

Campos:

id

sale_id

method

amount

payment_date

created_at
29. Tabela: financial_transactions
Objetivo

Livro financeiro.

Campos:

id

tenant_id

type

description

amount

reference_type

reference_id

transaction_date

created_at

Tipos:

INCOME

EXPENSE
30. Tabela: audit_logs

Campos:

id

tenant_id

user_id

action

table_name

record_id

old_data

new_data

ip

user_agent

created_at
31. Índices Importantes

Criar índices:

Produtos
tenant_id

sku

barcode

category_id
Vendas
tenant_id

sale_date

customer_id
Estoque
product_id

created_at
Financeiro
tenant_id

transaction_date
32. Soft Delete

Tabelas principais:

users
products
customers
suppliers
trips

Utilizar:

deleted_at

Nunca remover fisicamente dados importantes.

33. Integridade Referencial

Todas as relações deverão possuir:

FOREIGN KEY

Exemplo:

products.trip_id

REFERENCES trips.id
34. Transações Obrigatórias

Operações críticas deverão utilizar:

BEGIN

COMMIT

ROLLBACK

Exemplos:

Venda:

baixar estoque;
criar financeiro;
calcular lucro.

Tudo ou nada.

35. Preparação para Migrações Futuras

O modelo deverá permitir:

separar bancos por cliente;
adicionar marketplace;
adicionar aplicativo;
adicionar inteligência artificial;
adicionar integrações externas.
Encerramento da Parte 5

Este modelo define a base estrutural de dados do ImportControl.

A partir deste ponto, todos os módulos deverão ser desenvolvidos respeitando:

entidades;
relacionamentos;
isolamento SaaS;
histórico;
auditoria;
integridade financeira.