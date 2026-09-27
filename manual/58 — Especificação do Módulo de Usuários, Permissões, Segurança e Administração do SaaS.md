# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 58 — Especificação do Módulo de Usuários, Permissões, Segurança e Administração do SaaS

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Controle de usuários, níveis de acesso, permissões, segurança, multiempresa, auditoria e administração da plataforma SaaS

---

# 1. Objetivo

O módulo de Usuários e Segurança será responsável por controlar o acesso ao sistema, garantindo que cada pessoa visualize e execute somente as funções permitidas.

O objetivo é proporcionar:


Segurança dos dados

Controle de acesso

Separação de responsabilidades

Auditoria completa

Escalabilidade SaaS

Administração de clientes


---

# 2. Conceito SaaS

O ImportControl será desenvolvido como uma plataforma SaaS.

A arquitetura deverá permitir:


Uma instalação

Múltiplas empresas clientes

Dados isolados por empresa

Usuários independentes

Planos comerciais futuros


---

# 3. Modelo Multi-Tenant

Cada empresa será considerada um:


TENANT


Exemplo:


Tenant 001

Loja João Importados

Tenant 002

Empresa ABC Eletrônicos


---

# 4. Regra Principal Multi-Tenant

Todos os dados comerciais deverão possuir:

```sql
tenant_id

Exemplo:

products

id

tenant_id

name

price

Garantia:

Usuário da empresa A

NUNCA poderá acessar

dados da empresa B
5. Perfis de Usuário

O sistema terá inicialmente:

SUPER ADMIN

ADMINISTRADOR

USUÁRIO OPERACIONAL
6. Super Administrador

Perfil:

Desenvolvedor da plataforma

Responsável por:

Gerenciar empresas

Gerenciar usuários administradores

Controlar planos

Monitorar sistema

Acessar logs técnicos

Configurar parâmetros globais
7. Administrador da Empresa

Perfil:

Proprietário do negócio

Permissões:

Gerenciar usuários internos

Gerenciar produtos

Gerenciar vendas

Gerenciar financeiro

Visualizar relatórios

Configurar empresa
8. Usuário Operacional

Perfil:

Funcionário vendedor

Pode:

Cadastrar clientes

Realizar vendas

Consultar produtos

Consultar estoque permitido

Não pode:

Ver financeiro completo

Alterar custos

Criar usuários

Excluir dados
9. Tabela Users
CREATE TABLE users (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT NULL,

name VARCHAR(150),

email VARCHAR(150),

password VARCHAR(255),

role_id BIGINT,

status ENUM(
'ACTIVE',
'INACTIVE',
'BLOCKED'
),

last_login DATETIME,

created_at TIMESTAMP,

updated_at TIMESTAMP

);
10. Autenticação

O sistema deverá utilizar:

Login

Senha criptografada

Sessão segura

Controle de tentativa

Expiração sessão
11. Criptografia de Senhas

Obrigatório:

Password Hash

bcrypt

Argon2 recomendado

Nunca armazenar:

Senha em texto puro
12. Recuperação de Senha

Fluxo:

Usuário solicita recuperação

↓

Sistema envia token

↓

Usuário redefine senha

↓

Senha atualizada

↓

Registro auditoria
13. Tabela Password Resets
CREATE TABLE password_resets (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

user_id BIGINT,

token VARCHAR(255),

expires_at DATETIME,

used_at DATETIME,

created_at TIMESTAMP

);
14. Controle de Sessão

Registrar:

Login realizado

Logout

IP

Data/hora

Dispositivo

Navegador
15. Tabela User Sessions
CREATE TABLE user_sessions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

user_id BIGINT,

ip_address VARCHAR(50),

user_agent TEXT,

login_at DATETIME,

logout_at DATETIME

);
16. Controle de Tentativas

Proteção contra ataques:

Tentativas inválidas

Bloqueio temporário

Tempo de espera

Registro segurança
17. Dois Fatores de Autenticação (2FA)

Preparar suporte:

Google Authenticator

Aplicativo autenticador

Código temporário
18. Papéis e Permissões

Modelo:

Usuário

↓

Perfil

↓

Permissões

↓

Ações permitidas
19. Tabela Roles
CREATE TABLE roles (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

description TEXT

);
20. Tabela Permissions
CREATE TABLE permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

module VARCHAR(100),

description TEXT

);
21. Relação Role Permission
CREATE TABLE role_permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

role_id BIGINT,

permission_id BIGINT

);
22. Exemplos de Permissões
Produtos
products.view

products.create

products.edit

products.delete
Vendas
sales.view

sales.create

sales.cancel

sales.discount

sales.change_price
Financeiro
financial.view

financial.create

financial.export
23. Controle por Campo Sensível

Algumas informações devem possuir proteção adicional:

Custo dos produtos

Margem de lucro

Financeiro

Dados pessoais clientes

Exemplo:

Usuário vendedor:

Preço venda:

SIM


Custo produto:

NÃO
24. Administração da Empresa

O administrador poderá configurar:

Nome empresa

Logo

Dados contato

Moeda padrão

Configurações comerciais

Usuários
25. Tabela Companies
CREATE TABLE companies (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(150),

document VARCHAR(30),

email VARCHAR(150),

phone VARCHAR(30),

logo VARCHAR(255),

status VARCHAR(30),

created_at TIMESTAMP

);
26. Configurações do Tenant

Tabela:

tenant_settings

Campos:

tenant_id

timezone

currency

language

date_format

financial_settings
27. Logs do Sistema

Registrar ações importantes:

Login

Alterações

Exclusões

Exportações

Mudança permissões

Alterações financeiras
28. Tabela Audit Logs
CREATE TABLE audit_logs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

action VARCHAR(100),

module VARCHAR(100),

description TEXT,

ip_address VARCHAR(50),

created_at TIMESTAMP

);
29. Histórico de Alterações

Registrar:

Antes:

Preço:

R$500

Depois:

Preço:

R$450

Salvar:

Usuário

Data

Motivo

IP
30. Monitoramento do SaaS

Super Admin poderá visualizar:

Quantidade empresas

Usuários ativos

Uso sistema

Erros

Logs
31. Controle de Planos Futuro

Preparar estrutura:

Plano Free

Plano Básico

Plano Profissional

Plano Premium
32. Tabela Plans
CREATE TABLE plans (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

price DECIMAL(10,2),

limits JSON,

status VARCHAR(30)

);
33. Assinaturas

Preparar:

Empresa

Plano

Data início

Data renovação

Status

Tabela:

subscriptions
34. Limitações por Plano

Exemplo:

Plano básico:

Até 3 usuários

Até 1000 produtos

Sem BI avançado

Plano profissional:

Usuários ilimitados

Dashboard completo

Relatórios avançados
35. Segurança Aplicação

Implementar:

HTTPS obrigatório

Proteção CSRF

Proteção XSS

Prepared Statements

Validação entrada

Controle upload
36. Segurança Banco de Dados

Aplicar:

PDO

Queries parametrizadas

Usuários banco separados

Backup automático

Controle acesso
37. Backup

Preparar:

Backup diário

Backup semanal

Restauração

Histórico backups
38. API Security

Todas APIs devem possuir:

Autenticação

Token

Validação tenant

Controle permissão
39. Serviços Backend

Criar:

AuthService

UserService

PermissionService

TenantService

SecurityService

AuditService

BackupService
40. Controllers

Criar:

AuthController

UserController

RoleController

PermissionController

TenantController

AuditController
41. API Usuários

Endpoints:

POST /api/v1/auth/login

POST /api/v1/auth/logout

POST /api/v1/auth/reset-password


GET /api/v1/users

POST /api/v1/users

PUT /api/v1/users/{id}

DELETE /api/v1/users/{id}
42. Permissões Administração

Criar:

users.view

users.create

users.edit

users.delete

roles.manage

audit.view

tenant.manage
43. Auditoria Obrigatória

Registrar:

Quem fez

O que fez

Quando fez

Onde fez

Qual dado alterado
44. Regras de Negócio
Regra 1

Usuário sempre pertence a um tenant.

Regra 2

Super Admin não pertence a tenant.

Regra 3

Nenhum usuário acessa dados externos.

Regra 4

Ações críticas devem ser auditadas.

Regra 5

Usuários inativos não podem acessar sistema.

45. Critérios de Aceitação
[ ] Login funcionando

[ ] Recuperação senha funcionando

[ ] Controle perfis funcionando

[ ] Permissões funcionando

[ ] Multi-tenant funcionando

[ ] Auditoria funcionando

[ ] Logs funcionando

[ ] Segurança aplicada

[ ] Administração SaaS funcionando

[ ] Estrutura preparada para planos
Encerramento da Parte 58

O módulo de Usuários e Segurança será a base estrutural do ImportControl como SaaS.

Ele garantirá:

Escalabilidade

Segurança

Privacidade

Controle empresarial

Administração profissional

A arquitetura estará preparada para transformar o sistema de uma aplicação individual em uma plataforma comercial SaaS.