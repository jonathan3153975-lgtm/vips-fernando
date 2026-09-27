# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 49 — Especificação das Migrations, Seeds e Implantação Inicial do Banco de Dados

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Controle de versionamento do banco, criação automática das estruturas, dados iniciais e processo de implantação

---

# 1. Objetivo

O módulo de Migrations será responsável por controlar a evolução do banco de dados durante todo o ciclo de desenvolvimento do ImportControl.

O objetivo é garantir:


Banco sempre sincronizado com o código

Histórico das alterações

Instalação simplificada

Facilidade de manutenção

Controle de versões


---

# 2. Conceito de Migration

Migration representa uma alteração controlada no banco.

Exemplo:


Desenvolvedor cria tabela produtos

↓

Cria migration

↓

Executa migration

↓

Banco atualizado


---

# 3. Benefícios

Utilizar migrations permite:


Criar ambiente desenvolvimento

Criar ambiente homologação

Criar ambiente produção

Reverter alterações

Trabalhar em equipe


---

# 4. Estrutura de Diretórios

Criar:


database/

├── migrations/

│

├── seeds/

│

├── factories/

│

├── backups/

│

└── database.php


---

# 5. Controle de Versão

Criar tabela:

## migrations

```sql
CREATE TABLE migrations (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

migration VARCHAR(255),

batch INT,

executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

Exemplo:

001_create_users_table

002_create_products_table

003_create_sales_table
6. Padrão de Nome das Migrations

Formato:

AAAA_MM_DD_HORA_nome_descricao

Exemplo:

2026_08_01_100000_create_users_table.php
7. Ordem de Execução

A ordem deverá respeitar dependências.

Sequência:

1 - Sistema base

2 - Usuários

3 - Permissões

4 - Empresas

5 - Produtos

6 - Estoque

7 - Clientes

8 - Vendas

9 - Financeiro

10 - Relatórios
8. Migration Inicial do Sistema

Criar tabelas base:

tenants

users

roles

permissions

migrations

settings
9. Migration Tenants

Arquivo:

create_tenants_table.php

Código:

public function up()
{

CREATE TABLE tenants (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(150),

document VARCHAR(30),

email VARCHAR(150),

status VARCHAR(20),

created_at TIMESTAMP

);

}

Rollback:

public function down()
{

DROP TABLE tenants;

}
10. Migration Usuários

Criar:

create_users_table.php

Campos:

id

tenant_id

name

email

password

role_id

status

created_at
11. Migration Permissões

Criar:

create_roles_table.php

create_permissions_table.php

create_role_permissions_table.php

Estrutura:

Administrador

        ↓

Todas permissões


Usuário

        ↓

Permissões limitadas
12. Migration Configurações

Tabela:

settings
CREATE TABLE settings (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

key_name VARCHAR(100),

value TEXT

);

Guardar:

Nome empresa

Logo

Moeda padrão

Configurações sistema
13. Migration Produtos

Criar:

create_products_table.php

Dependências:

tenants

categories

brands
14. Migration Categorias

Criar:

create_product_categories_table.php

Dados:

Eletrônicos

Informática

Celulares

Acessórios
15. Migration Estoque

Criar:

create_warehouses_table.php

create_stock_table.php

create_stock_movements_table.php
16. Migration Clientes

Criar:

create_customers_table.php

Campos:

id

tenant_id

name

document

phone

email

address
17. Migration Vendas

Criar:

create_sales_table.php

create_sale_items_table.php

create_payments_table.php

Dependências:

Clientes

Produtos

Usuários
18. Migration Importações

Criar:

create_imports_table.php

create_import_items_table.php

create_travel_expenses_table.php
19. Migration Financeiro

Criar:

create_financial_accounts_table.php

create_financial_transactions_table.php

create_accounts_receivable_table.php

create_accounts_payable_table.php
20. Migration Auditoria

Criar:

create_audit_logs_table.php

Estrutura:

CREATE TABLE audit_logs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

action VARCHAR(100),

table_name VARCHAR(100),

record_id BIGINT,

old_values JSON,

new_values JSON,

created_at TIMESTAMP

);
21. Seeds

Seeds são dados iniciais carregados automaticamente.

Objetivo:

Criar ambiente funcional

Evitar cadastro manual

Padronizar instalação
22. Estrutura Seeds

Criar:

database/seeds/

├── DatabaseSeeder.php

├── RoleSeeder.php

├── PermissionSeeder.php

├── AdminSeeder.php

├── CategorySeeder.php

├── CurrencySeeder.php

└── SettingSeeder.php
23. Role Seeder

Criar perfis:

Developer

Administrador

Usuário

Exemplo:

Developer

Acesso total técnico


Administrador

Gestão completa empresa


Usuário

Operação diária
24. Permission Seeder

Criar permissões:

Usuários
users.view

users.create

users.edit

users.delete
Produtos
products.view

products.create

products.edit

products.delete
Financeiro
finance.view

finance.create

finance.approve
25. Admin Seeder

Criar usuário inicial:

Nome:

Administrador


Email:

admin@sistema.com


Senha:

definida instalação

Senha deve usar:

password_hash()
26. Currency Seeder

Criar moedas:

BRL

USD

EUR

Exemplo:

BRL

Real Brasileiro


USD

Dólar Americano
27. Category Seeder

Categorias padrão:

Eletrônicos

Celulares

Informática

Acessórios

Outros
28. Setting Seeder

Configurações:

Moeda padrão

Formato data

Timezone

Nome sistema

Versão
29. Factory

Preparar dados fictícios.

Exemplo:

50 produtos

20 clientes

100 vendas

Utilização:

Ambiente desenvolvimento

Testes
30. Comandos de Banco

Criar comandos:

Executar migrations
php artisan migrate
Reverter última migration
php artisan migrate:rollback
Recriar banco
php artisan migrate:fresh
Popular dados
php artisan db:seed
31. Classe Migration Manager

Criar:

app/Core/MigrationManager.php

Responsabilidades:

Encontrar migrations

Executar pendentes

Registrar execução

Rollback
32. Instalação Inicial do Sistema

Processo:

Criar banco vazio

↓

Configurar .env

↓

Executar migrations

↓

Executar seeds

↓

Criar usuário administrador

↓

Sistema pronto
33. Arquivo Installer

Criar:

install.php

Responsável por:

Verificar requisitos

Criar tabelas

Configurar ambiente

Criar administrador
34. Verificação de Requisitos

O instalador deve validar:

PHP >= 8.0

PDO habilitado

MySQL/MariaDB disponível

Permissão escrita

Extensões necessárias
35. Atualizações Futuras

Quando criar nova funcionalidade:

Exemplo:

Novo campo produto

↓

Nova migration

↓

Executar atualização
36. Backup Antes de Migration

Em produção:

Backup automático

↓

Executar migration

↓

Validar

↓

Liberar sistema
37. Ambiente de Desenvolvimento

Possuir:

Banco local

Banco homologação

Banco produção
38. Controle de Dados Sensíveis

Nunca inserir em seed:

Senhas reais

Tokens

Dados clientes reais

Informações financeiras
39. Documentação do Banco

Manter:

database.md

Modelo ER

Lista tabelas

Relacionamentos

Histórico alterações
40. Critérios de Aceitação
[ ] Migration Manager criado

[ ] Todas tabelas possuem migration

[ ] Rollback funcionando

[ ] Seeds funcionando

[ ] Usuário inicial criado

[ ] Permissões carregadas

[ ] Moedas cadastradas

[ ] Categorias cadastradas

[ ] Instalação automatizada

[ ] Backup previsto
Encerramento da Parte 49

O sistema de migrations e seeds garantirá que o ImportControl possa evoluir profissionalmente, permitindo:

Desenvolvimento organizado

Implantações seguras

Controle de versões

Instalações rápidas

Manutenção simplificada

A partir desta estrutura, novos módulos poderão ser adicionados sem comprometer o banco existente.