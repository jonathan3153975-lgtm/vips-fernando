# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 3 — Arquitetura SaaS Multi-Tenant

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Arquitetura SaaS, Multiempresa e Escalabilidade

---

# 1. Objetivo

O ImportControl deverá ser desenvolvido desde sua primeira versão como uma plataforma SaaS (Software as a Service).

Embora inicialmente possa existir apenas um cliente utilizando o sistema, toda a arquitetura deverá estar preparada para atender múltiplas empresas simultaneamente.

O sistema deverá permitir que diferentes empresas utilizem a mesma aplicação, compartilhando a mesma infraestrutura, porém mantendo isolamento completo dos dados.

---

# 2. Conceito SaaS

O sistema será disponibilizado como um serviço online.

Cada cliente terá:

- uma conta própria;
- usuários próprios;
- estoque próprio;
- produtos próprios;
- viagens próprias;
- clientes próprios;
- fornecedores próprios;
- configurações próprias;
- relatórios próprios.

Nenhum usuário poderá visualizar ou manipular dados pertencentes a outro cliente.

---

# 3. Conceito de Tenant

Um **Tenant** representa uma empresa ou organização cadastrada dentro do sistema.

Exemplo:

Tenant 001

Empresa:
Importadora Alpha

Usuários:

João
Maria

Dados:

Viagens
Produtos
Clientes
Vendas
Financeiro
-------------
Tenant 002

Empresa:
Importadora Beta

Usuários:

Carlos
Ana

Dados:

Viagens
Produtos
Clientes
Vendas
Financeiro


---

Mesmo utilizando o mesmo banco de dados, os dados serão completamente isolados.

---

# 4. Estratégia Multi-Tenant

O sistema utilizará o modelo:

## Banco compartilhado + Tenant ID

Também conhecido como:

Shared Database / Shared Schema

---

Todas as tabelas de negócio possuirão:
-----------
tenant_id

Exemplo:

Tabela:

products
id

tenant_id

name

sale_price

stock

created_at

---

Dados:
id | tenant_id | produto

1 | 10 | iPhone 15

2 | 10 | AirPods

3 | 20 | Notebook Dell

Usuário do tenant 10:

Visualiza:
iPhone 15

AirPods

Nunca verá:
Notebook Dell

---

# 5. Regras Obrigatórias de Isolamento

Toda consulta deverá obrigatoriamente considerar:
tenant_id


Nunca será permitido:

```sql
SELECT * FROM products;
Correto:
SELECT *
FROM products
WHERE tenant_id = ?


6. Tenant Middleware

Será criado um middleware responsável por identificar o tenant atual.

Fluxo:
Usuário acessa sistema

↓

Login

↓

Sistema identifica usuário

↓

Busca tenant_id

↓

Cria contexto da aplicação

↓

Todas operações utilizam tenant atual
7. Tenant Context

Criar uma classe responsável pelo contexto atual.

Exemplo:

TenantContext

Responsabilidades:

armazenar tenant atual;
disponibilizar tenant_id;
validar permissões;
controlar escopo das consultas.

Exemplo:

TenantContext::id();

Retorno:

15
8. Modelo de Usuários

Usuários pertencem obrigatoriamente a um tenant.

Estrutura:

users

id

tenant_id

name

email

password

role_id

status

last_login

created_at

updated_at

9. Usuário Desenvolvedor

O sistema terá um usuário especial:

Developer

Esse usuário não pertence a um tenant comum.

Ele possui acesso global.

Responsabilidades:

gerenciar clientes SaaS;
criar tenants;
bloquear empresas;
visualizar métricas;
administrar planos;
acessar logs globais.
10. Perfis de Usuário

Inicialmente:

Developer

Administrador da plataforma.

Permissões:

acesso total.
Usuário

Administrador da empresa.

Permissões:

gerenciar operações do próprio negócio.

A arquitetura deverá permitir futuramente:

Administrador

Financeiro

Vendedor

Estoquista

Consulta
11. Sistema RBAC

O controle de acesso será baseado em:

Role Based Access Control

Estrutura:

Usuário

↓

Role

↓

Permissions

↓

Actions


Exemplo:

Usuário:

Carlos

Role:

Vendedor

Permissões:

vendas.create

vendas.view

clientes.view


Sem permissão:

financeiro.delete

users.manage

12. Planos SaaS

O sistema deverá estar preparado para comercialização.

Tabela:

plans

Exemplo:

Plano Free

Plano Starter

Plano Professional

Plano Enterprise

Cada plano poderá possuir:

limite de usuários;
limite de produtos;
limite de armazenamento;
limite de vendas;
módulos disponíveis.
13. Assinaturas

Tabela:

subscriptions

Responsável por controlar:

plano contratado;
validade;
status;
pagamentos.

Estados:

ACTIVE

TRIAL

PAUSED

CANCELED

EXPIRED
14. Trial

Preparar para oferecer período gratuito.

Exemplo:

14 dias grátis

Durante o período:

Usuário possui acesso completo.

Após vencimento:

Sistema bloqueia recursos conforme política.

15. Estrutura de Planos

Tabela:

plans

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

16. Tabela Tenants

Estrutura:

tenants

id

name

slug

logo

document

email

phone

status

plan_id

created_at

updated_at

17. Configurações por Tenant

Cada empresa poderá possuir configurações próprias.

Tabela:

tenant_settings

id

tenant_id

key

value

created_at

updated_at


Exemplo:

currency = BRL

timezone = America/Sao_Paulo

language = pt_BR

18. Personalização Visual

Preparado para cada empresa possuir:

logo;
nome fantasia;
cores;
favicon.

Exemplo:

Empresa A:

Azul

Empresa B:

Verde

19. Domínio Personalizado (Futuro)

Preparar arquitetura para:

cliente.importcontrol.com.br

ou:

app.cliente.com.br
20. Onboarding de Clientes

Novo cliente deverá passar por fluxo:

Cadastro

↓

Confirmação de email

↓

Criação do Tenant

↓

Escolha do plano

↓

Configuração inicial

↓

Primeiro acesso

21. Processo de Criação do Tenant

Ao criar uma empresa:

Sistema deverá gerar:

tenant;
usuário administrador;
configurações padrão;
permissões;
preferências.
22. Exclusão de Tenant

Nunca excluir fisicamente.

Utilizar:

Soft Delete

Status:

ACTIVE

BLOCKED

DELETED
23. Segurança Multi-Tenant

Obrigatório:

validação do tenant em todas as consultas;
proteção contra alteração manual de IDs;
validação de permissões;
logs;
auditoria.
24. Auditoria SaaS

Registrar:

criação de empresa;
alteração de plano;
bloqueio;
exclusão;
login;
alterações críticas.

Tabela:

audit_logs

id

tenant_id

user_id

action

description

ip

user_agent

created_at

25. Métricas Globais do Desenvolvedor

Dashboard administrativo deverá apresentar:

Número de empresas

Usuários ativos

Receita mensal

Planos contratados

Uso de armazenamento

Quantidade de produtos cadastrados

Quantidade de vendas

26. Limitação por Plano

Criar serviço:

PlanLimitService

Responsável por validar:

Exemplo:

Plano Free:

50 produtos

Usuário tenta cadastrar:

51º produto

Sistema bloqueia.

27. Feature Flags

Preparar sistema para liberar funcionalidades por plano.

Exemplo:

feature_reports

feature_api

feature_mobile

feature_ai

28. Cache

Preparar cache por tenant.

Nunca compartilhar cache entre empresas.

Exemplo:

tenant_10_dashboard

tenant_20_dashboard
29. Armazenamento

Arquivos deverão possuir isolamento.

Estrutura:

storage/

tenants/

001/

products/

documents/


002/

products/

documents/

30. API Multi-Tenant

Todas as chamadas API deverão validar:

Token

Usuário

Tenant

Permissões

Exemplo:

Request:

GET /api/v1/products

Headers:

Authorization: Bearer TOKEN

Tenant: 10
31. Preparação para Escala

Arquitetura preparada para evolução:

Atual

Banco único.

Futuro

Separação por banco.

Exemplo:

Tenant pequeno

Banco compartilhado


Tenant Enterprise

Banco exclusivo
32. Migração de Tenant

Preparar ferramentas futuras:

Exportar empresa

Importar empresa

Migrar banco

Backup individual

33. Backup

Estratégia:

Backup diário completo.

Backup incremental.

Restauração por tenant.

34. LGPD

O sistema deverá respeitar:

direito de acesso;
exclusão de dados;
anonimização;
registro de consentimento.
35. Objetivo Final da Arquitetura SaaS

Ao final da implementação, o ImportControl deverá ser capaz de:

atender milhares de empresas;
manter dados isolados;
possuir planos comerciais;
controlar assinaturas;
permitir expansão internacional;
oferecer API;
possuir aplicativo mobile futuro;
crescer sem alteração estrutural.
Encerramento da Parte 3

Esta arquitetura transforma o ImportControl de um simples sistema administrativo em uma plataforma SaaS comercializável.

Todas as próximas partes deverão respeitar essa estrutura, principalmente:

banco de dados;
autenticação;
permissões;
módulos;
relatórios;
APIs.