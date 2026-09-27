# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 15 — Banco de Dados Completo e Modelo Relacional

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Arquitetura do banco de dados, modelo relacional e padrões de persistência

---

# 1. Objetivo

Esta seção define a arquitetura do banco de dados do ImportControl.

O banco deverá ser:

- relacional;
- escalável;
- seguro;
- organizado;
- preparado para SaaS Multi-Tenant;
- fácil de manutenção;
- compatível com PHP 8+ utilizando PDO.

---

# 2. Tecnologia do Banco

Banco recomendado:


MariaDB 10+


ou:


MySQL 8+


---

Características utilizadas:

- InnoDB;
- Foreign Keys;
- Transactions;
- Indexes;
- Views;
- Triggers quando necessário.

---

# 3. Padrões de Banco

## Nome das tabelas

Utilizar:


snake_case


Exemplo:

Correto:

```sql
product_categories

Evitar:

ProductCategories
4. Chaves Primárias

Todas as tabelas deverão possuir:

id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
5. Campos Padrão

Toda tabela operacional deverá possuir:

created_at DATETIME

updated_at DATETIME

deleted_at DATETIME NULL

Objetivo:

auditoria;
histórico;
soft delete.
6. Arquitetura Multi-Tenant

Regra fundamental:

Todas as tabelas de negócio deverão possuir:

tenant_id

Exemplo:

products

id

tenant_id

name

Nunca existirão dados compartilhados entre empresas.

7. Estrutura Geral do Banco

Visão macro:

users

|

roles

|

tenants

|

--------------------------------

TRIPS

PRODUCTS

SALES

FINANCE

CUSTOMERS

REPORTS

AUDIT

--------------------------------
8. Tabela tenants

Representa cada empresa cliente do SaaS.

CREATE TABLE tenants (

id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(150) NOT NULL,

document VARCHAR(30),

email VARCHAR(150),

phone VARCHAR(30),

status ENUM(
'ACTIVE',
'INACTIVE',
'BLOCKED'
),

plan_id BIGINT,

created_at DATETIME,

updated_at DATETIME,

deleted_at DATETIME NULL

);
9. Tabela plans

Planos SaaS.

Exemplo:

Básico;
Profissional;
Premium.
plans

id

name

price

max_users

max_products

status

created_at
10. Tabela users

Usuários do sistema.

users

id

tenant_id

role_id

name

email

password

phone

avatar

status

last_login

created_at

updated_at

deleted_at

Relacionamento:

tenant

1:N

users
11. Tabela roles

Perfis.

roles

id

tenant_id

name

description

is_system

created_at

Exemplo:

Administrador

Vendedor

Financeiro

Consulta
12. Tabela permissions

Permissões.

permissions

id

module

action

name

description

Exemplo:

products.create

sales.delete

finance.view
13. Tabela role_permissions

Relaciona perfil e permissões.

role_permissions

id

role_id

permission_id

Relacionamento:

roles

N:N

permissions
14. Tabela trips

Viagens.

trips

id

tenant_id

name

country

city

start_date

end_date

currency_id

exchange_rate

status

notes

created_at

updated_at

deleted_at
15. Tabela trip_expenses

Despesas da viagem.

trip_expenses

id

tenant_id

trip_id

category_id

description

currency_id

amount

exchange_rate

converted_amount

expense_date

is_allocatable

attachment

created_at

updated_at
16. Tabela currencies

Moedas.

currencies

id

code

name

symbol

status

Exemplo:

BRL

USD

EUR
17. Tabela exchange_rates

Histórico cambial.

exchange_rates

id

tenant_id

currency_id

rate

date

source

Objetivo:

Manter cotação histórica.

18. Tabela products

Produtos.

products

id

tenant_id

category_id

supplier_id

sku

barcode

name

description

image

unit

status

created_at

updated_at

deleted_at
19. Tabela product_categories

Categorias.

product_categories

id

tenant_id

name

description

status
20. Tabela suppliers

Fornecedores.

suppliers

id

tenant_id

name

country

email

phone

website

notes
21. Tabela purchases

Compras internacionais.

purchases

id

tenant_id

trip_id

supplier_id

purchase_date

currency_id

exchange_rate

total_original

total_converted

status

created_at
22. Tabela purchase_items

Itens comprados.

purchase_items

id

purchase_id

product_id

quantity

unit_price

converted_price

total

Relacionamento:

purchase

1:N

purchase_items
23. Tabela product_cost_history

Histórico de custos.

product_cost_history

id

tenant_id

product_id

old_cost

new_cost

reason

user_id

created_at
24. Tabela stock

Estoque atual.

stock

id

tenant_id

product_id

quantity

minimum_quantity

updated_at
25. Tabela stock_movements

Movimentações.

stock_movements

id

tenant_id

product_id

type

quantity

reference_type

reference_id

user_id

created_at

Tipos:

ENTRY

SALE

RETURN

LOSS

ADJUSTMENT
26. Tabela customers

Clientes.

customers

id

tenant_id

name

document

phone

email

address

type

created_at

updated_at

deleted_at
27. Tabela sales

Vendas.

sales

id

tenant_id

customer_id

user_id

sale_number

status

subtotal

discount

total

profit

created_at
28. Tabela sale_items

Itens da venda.

sale_items

id

sale_id

product_id

quantity

cost_price

sale_price

discount

profit
29. Tabela payments

Pagamentos.

payments

id

tenant_id

sale_id

method

amount

status

paid_at
30. Tabela accounts_receivable

Contas a receber.

accounts_receivable

id

tenant_id

sale_id

amount

due_date

paid_date

status
31. Tabela financial_transactions

Movimentação financeira.

financial_transactions

id

tenant_id

category_id

type

description

amount

origin_type

origin_id

transaction_date

status

created_at
32. Tabela financial_categories

Categorias financeiras.

financial_categories

id

tenant_id

name

type

parent_id

status
33. Tabela audit_logs

Auditoria geral.

audit_logs

id

tenant_id

user_id

module

action

old_data

new_data

ip

created_at
34. Tabela login_logs

Histórico de acesso.

login_logs

id

user_id

tenant_id

ip

device

success

created_at
35. Tabela notifications

Notificações.

notifications

id

tenant_id

user_id

title

message

type

read_at

created_at
36. Relacionamentos Principais
Empresa
Tenant

1:N

Users
Produto
Product

1:N

PurchaseItems


Product

1:N

SaleItems


Product

1:N

StockMovements
Viagem
Trip

1:N

Expenses


Trip

1:N

Purchases
Venda
Sale

1:N

SaleItems


Sale

1:N

Payments
37. Índices Obrigatórios

Criar índices:

Tenant
INDEX tenant_id
Busca de produtos
INDEX sku

INDEX barcode

INDEX name
Usuários
UNIQUE email
Datas
INDEX created_at
38. Integridade Referencial

Todas relações deverão utilizar:

FOREIGN KEY

Exemplo:

FOREIGN KEY(product_id)

REFERENCES products(id)
39. Transações Financeiras

Operações críticas devem utilizar:

BEGIN TRANSACTION

COMMIT

ROLLBACK

Exemplo:

Finalizar venda:

Criar venda

↓

Baixar estoque

↓

Registrar financeiro

↓

Confirmar

Caso falhe:

Rollback completo.

40. Views Recomendadas

Criar views:

vw_product_profit

Mostra:

custo;
venda;
lucro.
vw_cash_flow

Mostra:

entradas;
saídas;
saldo.
vw_stock_summary

Mostra:

estoque atual;
valor estoque.
41. Backup

Estratégia:

Diário:

Dump completo

Semanal:

Backup externo
42. Migrações

O projeto deverá utilizar migrations.

Estrutura:

database/

migrations/

001_create_users.sql

002_create_products.sql

003_create_sales.sql
43. Seeds

Criar dados iniciais:

Roles padrão

Permissões

Moedas

Categorias padrão
44. Regras de Manutenção

Nunca:

alterar tabela em produção manualmente;
apagar dados críticos;
remover histórico financeiro.

Sempre:

criar migration;
versionar;
testar rollback.
45. Critérios de Aceitação
[ ] Banco criado

[ ] Relacionamentos definidos

[ ] Multi-tenant funcionando

[ ] Índices configurados

[ ] Foreign Keys aplicadas

[ ] Histórico preservado

[ ] Soft Delete aplicado

[ ] Migrations criadas

[ ] Seeds funcionando
Encerramento da Parte 15

O modelo de banco definido cria uma base sólida para o crescimento do ImportControl.

A arquitetura permite:

múltiplas empresas;
grande volume de produtos;
rastreamento financeiro;
auditoria completa;
manutenção segura.