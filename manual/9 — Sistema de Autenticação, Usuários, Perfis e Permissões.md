# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 9 — Sistema de Autenticação, Usuários, Perfis e Permissões

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação funcional e técnica do módulo de identidade e acesso

---

# 1. Objetivo

Esta seção define o funcionamento completo do sistema de autenticação, gerenciamento de usuários, perfis de acesso e permissões do ImportControl.

O módulo deverá garantir:

- acesso seguro ao sistema;
- separação entre usuários da plataforma e usuários das empresas;
- controle granular de permissões;
- facilidade de administração;
- escalabilidade para novos perfis;
- rastreabilidade das ações.

---

# 2. Conceito Geral

O ImportControl possuirá dois níveis principais de acesso:
DEVELOPER

↓

PLATAFORMA SAAS

TENANT ADMIN

↓

EMPRESA CLIENTE

USUÁRIOS OPERACIONAIS

↓

FUNCIONÁRIOS DA EMPRESA


---

# 3. Tipos de Usuário

## 3.1 Developer

Usuário responsável pela administração da plataforma.

Não representa uma empresa usuária.

Responsabilidades:

- administrar tenants;
- administrar planos;
- acompanhar métricas;
- gerenciar configurações globais;
- acessar logs;
- realizar suporte.

---

## 3.2 Tenant Administrator

Administrador da empresa cliente.

Responsabilidades:

- cadastrar usuários;
- configurar empresa;
- administrar produtos;
- gerenciar permissões;
- acessar todos os módulos do tenant.

---

## 3.3 Usuário Operacional

Usuário vinculado a uma empresa.

Exemplos:

- vendedor;
- financeiro;
- estoquista;
- consulta.

---

# 4. Modelo de Autenticação

O sistema utilizará:


Email + Senha


---

Futuras possibilidades:

- autenticação Google;
- autenticação Microsoft;
- autenticação por código;
- autenticação em dois fatores.

---

# 5. Fluxo de Login

Processo:


Usuário acessa /login

↓

Informa email

↓

Informa senha

↓

Sistema busca usuário

↓

Valida status

↓

Valida senha

↓

Carrega tenant

↓

Carrega permissões

↓

Cria sessão

↓

Redireciona Dashboard


---

# 6. Tela de Login

## Objetivo

Permitir acesso seguro ao sistema.

---

Elementos:


Logo ImportControl

Campo Email

Campo Senha

Botão Entrar

Link Esqueci minha senha

Informações da versão


---

# 7. Validações de Login

O sistema deverá validar:

Email informado.

Senha informada.

Usuário ativo.

Tenant ativo.

Assinatura válida.

---

# 8. Mensagens de Erro

Nunca informar qual dado está errado.

Errado:


Senha incorreta


ou:


Usuário não existe


---

Correto:


Usuário ou senha inválidos.


---

Motivo:

Evitar enumeração de usuários.

---

# 9. Sessão do Usuário

Após login:

Criar contexto:


user_id

tenant_id

role_id

permissions

last_activity


---

# 10. Sessão Segura

Ao autenticar:

Executar:


session_regenerate_id()


---

Objetivo:

Evitar session fixation.

---

# 11. Logout

Fluxo:


Usuário clica sair

↓

Sessão destruída

↓

Logs registrados

↓

Redireciona login


---

# 12. Tabela users

Estrutura:

```sql
users

id

tenant_id

role_id

name

email

password

avatar

phone

status

last_login

created_at

updated_at

deleted_at
13. Status do Usuário

Valores:

ACTIVE

INACTIVE

BLOCKED

PENDING
ACTIVE

Usuário pode acessar.

INACTIVE

Usuário desativado.

BLOCKED

Bloqueio administrativo ou segurança.

PENDING

Cadastro aguardando confirmação.

14. Cadastro de Usuário

Somente:

Developer;
Tenant Administrator.

Dados:

Nome

Email

Telefone

Perfil

Senha inicial

Status
15. Primeiro Acesso

Fluxo:

Usuário criado

↓

Recebe convite

↓

Define senha

↓

Aceita termos

↓

Acessa sistema
16. Alteração de Senha

Usuário poderá:

alterar própria senha.

Administrador poderá:

redefinir senha.

Regra:

Administrador nunca visualiza senha atual.

17. Recuperação de Senha

Fluxo:

Usuário informa email

↓

Sistema gera token

↓

Envia email

↓

Usuário define nova senha

↓

Token invalidado
18. Token de Recuperação

Características:

único;
temporário;
uso único.

Validade:

Exemplo:

30 minutos.

19. Perfis (Roles)

O controle será baseado em perfis.

Exemplo:

Administrador

Gerente

Financeiro

Vendedor

Estoquista

Consulta
20. Estrutura da Tabela roles
roles

id

tenant_id

name

description

is_system

created_at

Campo:

is_system

Indica perfil protegido.

21. Perfis Padrão

Ao criar um tenant:

Sistema cria automaticamente:

Administrador

Acesso completo.

Consulta

Somente visualização.

22. Permissões

Uma permissão representa uma ação.

Formato:

modulo.acao

Exemplos:

Produtos:

products.view

products.create

products.edit

products.delete

Viagens:

trips.view

trips.create

trips.close

Financeiro:

finance.view

finance.create

finance.delete
23. Estrutura da Tabela permissions
permissions

id

module

action

name

description

created_at
24. Relacionamento Perfil x Permissão

Tabela:

role_permissions

id

role_id

permission_id

Exemplo:

Perfil:

Vendedor

Possui:

sales.view

sales.create

customers.view

Não possui:

finance.delete
25. Verificação de Permissão

Antes de executar uma ação:

Sistema verifica:

Usuário autenticado?

↓

Tenant válido?

↓

Possui permissão?

↓

Executa ação
26. Helper de Permissão

Criar:

PermissionService

Métodos:

can()

hasRole()

hasPermission()


Exemplo:

if(
 PermissionService::can(
 'products.delete'
 )
)
{
 executar();
}
27. Controle por Menu

O menu deverá respeitar permissões.

Exemplo:

Usuário sem:

reports.view

Não visualiza:

Relatórios
28. Proteção de Rotas

Não basta esconder menus.

Toda rota deverá validar.

Exemplo:

Usuário tenta acessar:

/finance/delete/10

Diretamente.

Sistema bloqueia.

29. Middleware de Autorização

Criar:

PermissionMiddleware

Uso:

Route

↓

PermissionMiddleware

↓

Controller
30. Matriz Inicial de Permissões
Dashboard
dashboard.view
Viagens
trips.view

trips.create

trips.edit

trips.close

trips.delete
Produtos
products.view

products.create

products.edit

products.delete
Estoque
stock.view

stock.adjust
Vendas
sales.view

sales.create

sales.cancel
Clientes
customers.view

customers.create

customers.edit

customers.delete
Financeiro
finance.view

finance.create

finance.delete
Relatórios
reports.view

reports.export
Usuários
users.view

users.create

users.edit

users.delete
31. Usuário Developer

O Developer possuirá painel separado.

URL:

/developer

Módulos:

Tenants

Planos

Assinaturas

Usuários

Logs

Métricas

Configurações
32. Dashboard Developer

Indicadores:

Total de empresas

Empresas ativas

Usuários cadastrados

Receita mensal

Planos vendidos

Uso do sistema
33. Bloqueio de Tenant

Developer poderá:

bloquear empresa;
suspender acesso;
alterar plano.

Ao bloquear:

Todos usuários do tenant ficam impedidos.

34. Impersonation (Acesso Assistido)

Funcionalidade futura.

Permite ao Developer entrar temporariamente em um tenant.

Regras:

registrar ação;
solicitar confirmação;
gerar log;
possuir tempo limitado.
35. Auditoria de Usuários

Registrar:

Login.

Logout.

Falha de login.

Alteração de senha.

Alteração de permissão.

Criação de usuário.

Exclusão de usuário.

36. Logs de Acesso

Tabela:

login_logs

Campos:

id

user_id

tenant_id

ip

device

success

created_at
37. Segurança de Usuários

Obrigatório:

senha criptografada;
sessão segura;
permissões server-side;
logs;
bloqueio de tentativas.
38. Preparação para 2FA

Arquitetura deverá permitir:

Tabela futura:

user_two_factor

Campos:

user_id

secret

enabled

created_at
39. Critérios de Aceitação

O módulo será considerado concluído quando:

[ ] Login funcionando

[ ] Logout funcionando

[ ] Recuperação de senha

[ ] Controle de sessão

[ ] RBAC funcionando

[ ] Tenant isolado

[ ] Permissões aplicadas

[ ] Logs registrados

[ ] Developer separado

[ ] Segurança validada
Encerramento da Parte 9

Este módulo estabelece a base de identidade e controle de acesso do ImportControl.

A partir desta estrutura, todos os módulos seguintes deverão utilizar:

autenticação centralizada;
permissões;
contexto do tenant;
auditoria.