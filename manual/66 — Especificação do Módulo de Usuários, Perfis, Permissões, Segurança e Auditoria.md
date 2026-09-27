# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 66 — Especificação do Módulo de Usuários, Perfis, Permissões, Segurança e Auditoria

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Gestão de usuários, controle de acesso, segurança da aplicação, rastreamento de ações e governança do sistema

---

# 1. Objetivo

O módulo de Usuários e Segurança será responsável por controlar quem pode acessar o sistema, quais funcionalidades cada usuário pode utilizar e quais ações foram realizadas.

O objetivo é garantir:


Segurança dos dados

Controle de acesso

Separação de responsabilidades

Rastreabilidade completa

Proteção contra ações indevidas

Conformidade empresarial


---

# 2. Conceito Geral

O modelo de segurança será baseado em:


Empresa (Tenant)

    ↓

Usuários

    ↓

Perfis

    ↓

Permissões

    ↓

Ações realizadas

    ↓

Auditoria


---

# 3. Requisitos Funcionais

O módulo deverá permitir:


RF001 - Criar usuários

RF002 - Editar usuários

RF003 - Bloquear usuários

RF004 - Criar perfis

RF005 - Definir permissões

RF006 - Controlar acesso por módulo

RF007 - Registrar ações

RF008 - Controlar sessões

RF009 - Recuperar senha

RF010 - Aplicar políticas de segurança


---

# 4. Conceito Multiusuário

Uma empresa poderá possuir:


Administrador

Gerente

Vendedor

Financeiro

Estoque

Operador


Cada usuário terá permissões específicas.

---

# 5. Cadastro de Usuários

Dados:


Nome completo

Email

Telefone

Senha

Perfil

Status

Último acesso

Empresa vinculada


---

# 6. Tabela Users

```sql
CREATE TABLE users (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT NOT NULL,

name VARCHAR(150),

email VARCHAR(150),

password VARCHAR(255),

role_id BIGINT,

status VARCHAR(30),

last_login DATETIME,

created_at TIMESTAMP,

updated_at TIMESTAMP

);
7. Status do Usuário

Estados:

ACTIVE

Usuário ativo


INACTIVE

Usuário desativado


BLOCKED

Usuário bloqueado
8. Login no Sistema

O processo deverá:

Usuário informa email

        ↓

Sistema valida senha

        ↓

Verifica status

        ↓

Carrega permissões

        ↓

Cria sessão

        ↓

Registra acesso
9. Segurança de Senha

Requisitos:

Senha criptografada

Nunca armazenar senha pura

Mínimo de caracteres configurável

Bloqueio após tentativas
10. Criptografia

Utilizar:

bcrypt

ou

Argon2
11. Recuperação de Senha

Fluxo:

Usuário solicita recuperação

        ↓

Sistema gera token

        ↓

Envia email

        ↓

Usuário cria nova senha

        ↓

Token invalidado
12. Tabela Password Resets
CREATE TABLE password_resets (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

user_id BIGINT,

token VARCHAR(255),

expires_at DATETIME,

used_at DATETIME,

created_at TIMESTAMP

);
13. Perfis de Acesso

O sistema utilizará RBAC:

(Role Based Access Control)

Usuário

↓

Perfil

↓

Permissões
14. Tabela Roles
CREATE TABLE roles (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

description TEXT,

created_at TIMESTAMP

);
15. Perfis Padrão

Criar inicialmente:

Administrador

Permissão:

Acesso total
Gerente

Permissão:

Vendas

Estoque

Relatórios

Clientes
Vendedor

Permissão:

Criar vendas

Consultar produtos

Consultar clientes
Financeiro

Permissão:

Contas

Fluxo caixa

Relatórios financeiros
Estoquista

Permissão:

Produtos

Estoque

Inventário
16. Permissões

Cada ação do sistema será uma permissão.

Exemplo:

products.view

products.create

products.edit

products.delete

sales.create

financial.view
17. Tabela Permissions
CREATE TABLE permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

module VARCHAR(100),

action VARCHAR(100),

description TEXT

);
18. Relação Perfil x Permissão

Tabela:

CREATE TABLE role_permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

role_id BIGINT,

permission_id BIGINT

);
19. Controle de Acesso

Antes de qualquer ação:

Usuário solicita recurso

        ↓

Sistema verifica permissão

        ↓

Autoriza ou bloqueia
20. Middleware de Segurança

Criar:

AuthMiddleware

PermissionMiddleware

TenantMiddleware

Responsabilidades:

Validar usuário

Validar empresa

Validar permissão
21. Controle de Sessões

Registrar:

Login

Logout

IP

Navegador

Data/hora

Dispositivo
22. Tabela User Sessions
CREATE TABLE user_sessions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

user_id BIGINT,

token VARCHAR(255),

ip_address VARCHAR(50),

user_agent TEXT,

last_activity DATETIME,

created_at TIMESTAMP

);
23. Login em Dois Fatores (2FA)

Preparar suporte:

Aplicativo autenticador

Código temporário

QR Code
24. Tabela User Two Factor
CREATE TABLE user_two_factor (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

user_id BIGINT,

secret VARCHAR(255),

enabled BOOLEAN,

created_at TIMESTAMP

);
25. Auditoria do Sistema

Toda ação importante deverá ser registrada.

Exemplos:

Usuário criou produto

Usuário alterou preço

Usuário cancelou venda

Usuário exportou relatório
26. Tabela Audit Logs
CREATE TABLE audit_logs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

module VARCHAR(100),

action VARCHAR(100),

description TEXT,

old_data JSON,

new_data JSON,

ip_address VARCHAR(50),

created_at TIMESTAMP

);
27. Dados Auditados

Registrar:

Antes da alteração

Depois da alteração

Usuário responsável

Data

IP
28. Histórico de Alterações

Exemplo:

Produto:

Preço anterior:

R$1.000


Novo preço:

R$1.200


Alterado por:

Administrador


Data:

01/08/2026
29. Logs de Segurança

Registrar:

Login realizado

Login falhou

Senha alterada

Usuário bloqueado

Permissão alterada
30. Tabela Security Logs
CREATE TABLE security_logs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

user_id BIGINT,

event VARCHAR(100),

description TEXT,

ip_address VARCHAR(50),

created_at TIMESTAMP

);
31. Bloqueio por Tentativas

Regra:

5 tentativas inválidas

↓

Bloquear usuário temporariamente
32. Controle de IP

Preparar:

Lista permitida

Lista bloqueada

Registro de acessos suspeitos
33. Controle por Empresa

Todo acesso deverá validar:

Usuário pertence ao tenant?

Dados pertencem ao tenant?

Permissão pertence ao tenant?
34. Separação de Dados SaaS

Regra fundamental:

Nenhum usuário poderá consultar:

Dados de outra empresa

Produtos externos

Clientes externos

Financeiro externo
35. Serviços Backend

Criar:

UserService

RoleService

PermissionService

AuthService

AuditService

SecurityService
36. Controllers

Criar:

UserController

RoleController

PermissionController

AuthController

AuditController
37. API Usuários

Endpoints:

GET /api/v1/users

POST /api/v1/users

PUT /api/v1/users/{id}

POST /api/v1/users/{id}/block

GET /api/v1/users/{id}/logs
38. API Autenticação

Endpoints:

POST /api/v1/auth/login

POST /api/v1/auth/logout

POST /api/v1/auth/password-reset

POST /api/v1/auth/change-password
39. Permissões Administrativas

Criar:

users.view

users.create

users.edit

users.delete

roles.manage

permissions.manage

audit.view
40. Política de Segurança

Configurar:

Expiração senha

Tamanho mínimo

Histórico senhas

Tempo sessão

Bloqueio automático
41. Relatórios de Segurança

Criar:

Últimos acessos

Usuários ativos

Falhas login

Alterações críticas

Ações administrativas
42. Alertas de Segurança

Gerar:

Muitos logins inválidos

Novo dispositivo

Alteração permissão

Acesso suspeito
43. Regras de Negócio
Regra 1

Usuário sempre pertence a uma empresa.

Regra 2

Administrador não pode ser excluído.

Regra 3

Toda alteração crítica gera auditoria.

Regra 4

Permissões devem ser validadas no backend.

Regra 5

Logs não podem ser apagados por usuários comuns.

44. Critérios de Aceitação
[ ] Cadastro usuários funcionando

[ ] Login funcionando

[ ] Recuperação senha funcionando

[ ] Perfis funcionando

[ ] Permissões funcionando

[ ] Controle tenant funcionando

[ ] Auditoria funcionando

[ ] Logs segurança funcionando

[ ] Bloqueio funcionando

[ ] Sessões funcionando

[ ] Relatórios segurança funcionando
Encerramento da Parte 66

O módulo de Usuários e Segurança será responsável por garantir que o ImportControl possa operar como uma plataforma SaaS profissional.

A estrutura permitirá:

Controle completo de acesso

Proteção de informações

Rastreabilidade empresarial

Gestão multiusuário

Auditoria de operações

Este módulo será essencial para empresas com equipes, vendedores e múltiplos operadores utilizando o sistema.