# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 30 — Especificação dos Módulos de Usuário, Permissões e Controle de Acesso

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Gestão de usuários, perfis, permissões, segurança e administração do sistema

---

# 1. Objetivo

Este módulo será responsável por controlar quem pode acessar o sistema e quais ações cada usuário poderá executar.

O objetivo é garantir:

- segurança dos dados;
- separação de responsabilidades;
- controle empresarial;
- preparação para SaaS multiempresa.

---

# 2. Conceito de Controle de Acesso

O sistema utilizará o modelo:


Empresa (Tenant)

↓

Usuário

↓

Perfil

↓

Permissões

↓

Ações disponíveis


---

Exemplo:


Empresa:
Importadora João Ltda

Usuário:
Carlos

Perfil:
Vendedor

Permissões:
Criar venda
Consultar produtos
Consultar clientes


---

# 3. Tipos de Usuários

O sistema possuirá inicialmente dois níveis principais.

---

# 3.1 Desenvolvedor / Administrador SaaS

Responsável pela plataforma.

Pode:


Gerenciar empresas

Criar usuários administradores

Visualizar logs

Configurar sistema

Gerenciar planos

Monitorar utilização


---

# 3.2 Usuário da Empresa

Responsável pela operação.

Pode:


Cadastrar produtos

Registrar vendas

Controlar estoque

Cadastrar clientes

Gerenciar financeiro


---

# 4. Estrutura de Perfis

Criar perfis padrão:


SUPER_ADMIN

ADMIN

MANAGER

SELLER

FINANCIAL

VIEWER


---

# 5. Perfil SUPER_ADMIN

Usuário desenvolvedor.

Permissões:


Todas as permissões


Pode:

- acessar qualquer tenant;
- administrar plataforma;
- alterar configurações globais.

---

# 6. Perfil ADMIN

Administrador da empresa.

Responsável por:


Configuração da empresa

Usuários

Produtos

Financeiro

Relatórios


---

# 7. Perfil MANAGER

Gerente operacional.

Pode:


Produtos

Estoque

Compras

Vendas

Clientes

Relatórios


---

# 8. Perfil SELLER

Vendedor.

Pode:


Consultar produtos

Criar vendas

Cadastrar clientes

Consultar estoque


---

Não pode:


Excluir produtos

Alterar custos

Visualizar financeiro completo


---

# 9. Perfil FINANCIAL

Responsável financeiro.

Pode:


Contas pagar

Contas receber

Fluxo de caixa

Relatórios financeiros


---

Não pode:


Alterar produtos

Alterar estoque


---

# 10. Perfil VIEWER

Somente consulta.

Pode:


Dashboard

Relatórios permitidos

Consultas


---

Não pode:


Criar

Editar

Excluir


---

# 11. Banco de Dados de Usuários

## users

Campos:

```sql
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
12. Perfis
roles
id

tenant_id

name

description

is_system

created_at

Campo:

is_system

Define:

Perfil padrão do sistema

ou

Perfil criado pela empresa
13. Permissões
permissions
id

name

module

description

Exemplos:

products.create

products.edit

products.delete

sales.create

financial.view
14. Relacionamento Perfil x Permissão

Tabela:

role_permissions
id

role_id

permission_id

Exemplo:

Perfil:

SELLER

Possui:

sales.create

customers.create

products.view
15. Permissões por Módulo

Organizar:

Dashboard

Produtos

Estoque

Clientes

Fornecedores

Viagens

Compras

Vendas

Financeiro

Relatórios

Usuários
16. Permissões do Dashboard

Criar:

dashboard.view

dashboard.export
17. Permissões de Produtos

Criar:

products.view

products.create

products.edit

products.delete

products.change_price

products.view_cost
18. Permissões de Estoque

Criar:

stock.view

stock.adjust

stock.entry

stock.remove
19. Permissões de Vendas

Criar:

sales.view

sales.create

sales.edit

sales.cancel

sales.discount

sales.change_price
20. Permissões Financeiras

Criar:

financial.view

financial.create

financial.edit

financial.delete

financial.export
21. Permissões de Usuários

Criar:

users.view

users.create

users.edit

users.delete

users.permissions
22. Tela de Usuários

Local:

Configurações

↓

Usuários

Exibição:

Nome

Email

Perfil

Status

Último acesso

Ações
23. Cadastro de Usuário

Campos:

Nome

Email

Telefone

Perfil

Senha

Status

Opções:

Enviar convite

Criar senha manual
24. Convite de Usuário

Fluxo:

Administrador cria usuário

↓

Sistema envia email

↓

Usuário define senha

↓

Acessa sistema
25. Recuperação de Senha

Fluxo:

Usuário informa email

↓

Sistema gera token

↓

Envio link seguro

↓

Nova senha
26. Controle de Sessão

Registrar:

Login

Logout

Data

IP

Navegador

Tabela:

user_sessions
id

user_id

token

ip

user_agent

created_at

expires_at
27. Controle de Dispositivos

Permitir visualizar:

Sessões ativas

Computadores conectados

Celulares conectados

Ações:

Encerrar sessão
28. Autenticação

Fluxo:

Usuário acessa login

↓

Sistema valida email

↓

Valida senha

↓

Cria sessão

↓

Carrega permissões

↓

Redireciona dashboard
29. Segurança de Senhas

Obrigatório:

Utilizar:

password_hash()

Nunca armazenar:

Senha em texto puro
30. Regras de Senha

Configurar:

Mínimo:

8 caracteres

Recomendado:

Maiúscula

Minúscula

Número

Caracter especial
31. Autenticação em Dois Fatores (Futuro)

Preparar:

2FA

Google Authenticator

Email OTP

Tabela futura:

user_two_factor
id

user_id

secret

enabled
32. Auditoria de Usuários

Registrar:

Criação usuário

Alteração perfil

Mudança senha

Login

Logout

Exclusão

Tabela:

user_audit_logs
id

user_id

action

description

ip

created_at
33. Bloqueio de Conta

Após tentativas:

Exemplo:

5 tentativas inválidas

Ação:

Bloquear temporariamente

Campos:

Adicionar:

failed_attempts

blocked_until
34. Controle de Acesso por Tenant

Regra principal:

Usuário pertence a uma empresa

Toda consulta:

WHERE tenant_id = usuario.tenant_id
35. Impersonação Administrativa

Somente SUPER_ADMIN.

Permitir:

Entrar temporariamente em uma empresa

Objetivo:

suporte;
diagnóstico;
manutenção.

Registrar:

Quem acessou

Qual empresa

Quando

Motivo
36. Tela Administrador SaaS

Menu:

Empresas

Usuários

Planos

Uso do sistema

Logs

Configurações
37. Monitoramento de Uso

Mostrar:

Quantidade usuários

Produtos cadastrados

Vendas realizadas

Espaço utilizado

Último acesso
38. Limites por Plano SaaS

Preparar:

Exemplo:

Plano Básico:

3 usuários

1000 produtos

500 vendas/mês

Plano Profissional:

Usuários ilimitados

Produtos ilimitados

Relatórios avançados
39. Bloqueio por Plano

Quando atingir limite:

Exemplo:

Limite de produtos atingido.

Faça upgrade do plano.
40. Serviços Backend

Criar:

UserService

PermissionService

RoleService

AuthService

SessionService

AuditService
41. Controllers

Criar:

AuthController

UserController

RoleController

PermissionController

AdminController
42. Middleware

Implementar:

AuthMiddleware

PermissionMiddleware

TenantMiddleware

AdminMiddleware
43. Critérios de Aceitação
[ ] Login funcionando

[ ] Controle de sessão

[ ] Perfis criados

[ ] Permissões funcionando

[ ] Usuários isolados por empresa

[ ] Auditoria ativa

[ ] Recuperação de senha

[ ] Segurança aplicada

[ ] Estrutura preparada para SaaS
Encerramento da Parte 30

O módulo de usuários e permissões estabelece a base de segurança do ImportControl.

Com essa arquitetura, o sistema estará preparado para:

Uma empresa pequena

↓

Várias empresas

↓

Plataforma SaaS comercial

Garantindo:

segurança;
controle;
rastreabilidade;
escalabilidade.