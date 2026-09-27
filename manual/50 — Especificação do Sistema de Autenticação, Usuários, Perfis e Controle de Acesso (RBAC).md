# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 50 — Especificação do Sistema de Autenticação, Usuários, Perfis e Controle de Acesso (RBAC)

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Sistema de login, gerenciamento de usuários, níveis de acesso, permissões, segurança e auditoria

---

# 1. Objetivo

O sistema de autenticação será responsável por controlar quem pode acessar o ImportControl e quais funcionalidades cada usuário poderá utilizar.

O objetivo é garantir:


Segurança dos dados

Separação de responsabilidades

Controle de acesso

Rastreamento das ações

Proteção das informações financeiras


---

# 2. Modelo de Acesso

O sistema utilizará RBAC:

(Role Based Access Control)

Estrutura:


Usuário

↓

Perfil (Role)

↓

Permissões

↓

Funcionalidades do sistema


---

# 3. Níveis Principais de Usuário

O sistema possuirá inicialmente:


Developer

Administrador

Usuário Operacional


---

# 4. Usuário Developer

Perfil reservado ao desenvolvedor do sistema.

Responsabilidades:


Gerenciar empresas cadastradas

Gerenciar usuários globais

Visualizar informações técnicas

Gerenciar configurações SaaS

Acessar ferramentas administrativas


---

## Restrições

O Developer não deve:


Alterar dados comerciais sem autorização

Manipular vendas diretamente

Visualizar informações privadas sem registro


Toda ação deverá gerar auditoria.

---

# 5. Usuário Administrador

Representa o proprietário do negócio.

Permissões:


Gerenciar usuários

Cadastrar produtos

Controlar estoque

Registrar importações

Gerenciar vendas

Visualizar financeiro

Emitir relatórios

Configurar empresa


---

# 6. Usuário Operacional

Usuário responsável pela operação diária.

Exemplos:


Vendedor

Funcionário

Auxiliar administrativo


Pode:


Cadastrar clientes

Registrar vendas

Consultar produtos

Atualizar informações permitidas


Não pode:


Visualizar lucro

Alterar custos

Acessar financeiro completo

Gerenciar usuários


---

# 7. Tabela users

Estrutura:

```sql
CREATE TABLE users (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT NOT NULL,

role_id BIGINT NOT NULL,

name VARCHAR(150) NOT NULL,

email VARCHAR(150) UNIQUE NOT NULL,

password VARCHAR(255) NOT NULL,

phone VARCHAR(30),

avatar VARCHAR(255),

status ENUM(
'ACTIVE',
'INACTIVE',
'BLOCKED'
)
DEFAULT 'ACTIVE',

last_login DATETIME NULL,

password_changed_at DATETIME NULL,

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

updated_at TIMESTAMP NULL

);
8. Tabela Roles

Representa os perfis.

CREATE TABLE roles (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

slug VARCHAR(100),

description TEXT,

created_at TIMESTAMP

);

Exemplo:

Administrador

admin


Usuário

user


Developer

developer
9. Tabela Permissions

Representa ações disponíveis.

CREATE TABLE permissions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

name VARCHAR(100),

slug VARCHAR(150),

module VARCHAR(100),

description TEXT

);

Exemplo:

Criar produto

products.create


Visualizar financeiro

finance.view
10. Tabela Role Permissions

Relacionamento:

CREATE TABLE role_permissions (

role_id BIGINT,

permission_id BIGINT,

PRIMARY KEY(
role_id,
permission_id
)

);
11. Tabela User Sessions

Controlar sessões ativas.

CREATE TABLE user_sessions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

user_id BIGINT,

token VARCHAR(255),

ip_address VARCHAR(50),

user_agent TEXT,

expires_at DATETIME,

created_at TIMESTAMP

);
12. Processo de Login

Fluxo:

Usuário informa email e senha

↓

Sistema busca usuário

↓

Verifica status

↓

Valida senha

↓

Carrega permissões

↓

Cria sessão

↓

Redireciona dashboard
13. Tela de Login

Layout:

---------------------------------

        ImportControl


Email

[________________]


Senha

[________________]


[ Entrar ]



Esqueci minha senha

---------------------------------
14. Validação de Login

Verificar:

Email existente

Senha correta

Usuário ativo

Empresa ativa

Permissão válida
15. Segurança de Senha

Obrigatório utilizar:

password_hash()

password_verify()

Nunca armazenar:

Senha em texto puro

Senha reversível

Senha em logs
16. Política de Senhas

Configuração mínima:

Mínimo 8 caracteres

Pelo menos uma letra

Pelo menos um número

Recomendado símbolo especial
17. Recuperação de Senha

Fluxo:

Usuário solicita recuperação

↓

Sistema gera token

↓

Envia email

↓

Usuário define nova senha

↓

Token invalidado
18. Tabela Password Reset
CREATE TABLE password_resets (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

user_id BIGINT,

token VARCHAR(255),

expires_at DATETIME,

used_at DATETIME NULL,

created_at TIMESTAMP

);
19. Middleware de Autenticação

Criar:

AuthMiddleware

Responsável:

Verificar sessão

Validar usuário

Bloquear acesso não autorizado

Fluxo:

Request

↓

Middleware

↓

Usuário autenticado?

↓

Controller
20. Middleware de Permissão

Criar:

PermissionMiddleware

Exemplo:

requirePermission(
'finance.view'
);

Caso negado:

HTTP 403

Acesso não autorizado
21. Controle por Módulo

Permissões organizadas:

dashboard

users

products

stock

sales

customers

finance

reports

settings
22. Permissões Dashboard

Exemplo:

dashboard.view
23. Permissões Usuários
users.view

users.create

users.edit

users.delete
24. Permissões Produtos
products.view

products.create

products.edit

products.delete

products.change_cost
25. Permissões Estoque
stock.view

stock.adjust

stock.transfer
26. Permissões Vendas
sales.view

sales.create

sales.cancel

sales.discount
27. Permissões Financeiro
finance.view

finance.create

finance.edit

finance.delete

finance.reports
28. Permissões Relatórios
reports.view

reports.export
29. Ocultação de Interface

Além da segurança backend:

O frontend deverá esconder menus:

Exemplo:

Usuário sem permissão financeira:

Menu Financeiro

não aparece

Importante:

A ocultação visual NÃO substitui validação backend.

30. Auditoria de Login

Registrar:

Login realizado

Login falhou

Logout

Alteração senha

Bloqueio usuário
31. Tabela Audit Logs
CREATE TABLE audit_logs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

event VARCHAR(100),

description TEXT,

ip_address VARCHAR(50),

created_at TIMESTAMP

);
32. Bloqueio por Tentativas

Implementar proteção:

5 tentativas inválidas

↓

Bloqueio temporário

Tabela:

CREATE TABLE login_attempts (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

email VARCHAR(150),

ip_address VARCHAR(50),

success BOOLEAN,

created_at TIMESTAMP

);
33. Controle de Sessão

Configurar:

Tempo máximo sessão

Encerramento automático

Logout manual

Sessões múltiplas
34. Tempo de Expiração

Padrão:

8 horas


Configuração futura:

Admin define período
35. Segurança Contra Ataques

Implementar:

Proteção CSRF

Proteção XSS

Rate Limit Login

Prepared Statements

Headers segurança
36. Cabeçalhos HTTP

Adicionar:

X-Frame-Options

Content-Security-Policy

X-XSS-Protection

Strict-Transport-Security
37. Login em Dois Fatores (Preparação)

Arquitetura preparada para:

2FA

Google Authenticator

Código email

Tabela futura:

user_two_factor

id

user_id

secret

enabled
38. Gerenciamento de Usuários

Administrador poderá:

Criar usuário

Editar dados

Alterar perfil

Ativar/desativar

Resetar senha
39. Tela Usuários

Layout:

Usuários

---------------------------------

Nome

Email

Perfil

Status

Ações


[+ Novo usuário]

---------------------------------
40. Cadastro Usuário

Campos:

Nome

Email

Telefone

Perfil

Senha inicial

Status
41. Histórico de Alterações

Registrar:

Quem alterou

Quando

Campo alterado

Valor anterior

Novo valor
42. Usuário Principal da Empresa

Cada tenant deverá possuir:

1 administrador principal

Regras:

Não pode excluir

Pode transferir propriedade
43. Exclusão de Usuário

Utilizar:

Soft Delete

Nunca apagar:

Histórico vendas

Auditoria

Registros financeiros
44. API de Autenticação

Preparar endpoints:

POST /api/v1/auth/login

POST /api/v1/auth/logout

POST /api/v1/auth/reset-password

GET /api/v1/auth/me
45. Critérios de Aceitação
[ ] Login funcionando

[ ] Sessão segura

[ ] Controle RBAC

[ ] Permissões funcionando

[ ] Recuperação senha

[ ] Auditoria acessos

[ ] Bloqueio tentativas

[ ] Controle multiempresa

[ ] Usuários gerenciáveis

[ ] Middleware aplicado

[ ] Código preparado para 2FA
Encerramento da Parte 50

O sistema de autenticação e autorização será a base de segurança do ImportControl.

A arquitetura permitirá:

Controle total pelo proprietário

Separação entre empresas

Proteção de dados financeiros

Escalabilidade SaaS

Auditoria completa

Com este módulo, o sistema estará preparado para operar com múltiplos clientes e diferentes níveis de usuários.