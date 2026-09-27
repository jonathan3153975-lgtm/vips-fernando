# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 19 — Módulo Administrativo do Desenvolvedor SaaS

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação funcional e técnica do painel administrativo do desenvolvedor

---

# 1. Objetivo

O módulo Administrativo do Desenvolvedor será responsável pela administração global da plataforma SaaS.

Esse módulo será utilizado exclusivamente pelo proprietário/desenvolvedor do sistema para controlar:

- empresas cadastradas;
- planos SaaS;
- usuários;
- assinaturas;
- permissões;
- métricas;
- suporte;
- manutenção;
- auditoria global.

---

# 2. Conceito do Administrador SaaS

O administrador do sistema não representa uma empresa cliente.

Ele possui uma visão global da plataforma.

Fluxo:


Desenvolvedor

↓

Plataforma ImportControl

↓

Empresas clientes

↓

Usuários finais


---

# 3. Nível de Acesso

Criar perfil especial:


SUPER_ADMIN


---

Características:

- acesso total;
- independente de tenant;
- gerencia configurações globais;
- visualiza métricas gerais.

---

# 4. Separação entre Admin SaaS e Usuário Empresa

Regra:


SUPER_ADMIN

≠

ADMIN_EMPRESA


---

## SUPER_ADMIN

Administra o sistema.

---

## ADMIN_EMPRESA

Administra apenas sua empresa.

---

# 5. Painel Administrativo

Dashboard inicial:


ImportControl Admin

Empresas Ativas

Usuários Totais

Receita SaaS

Uso da Plataforma

Gráficos

Crescimento mensal

Novos clientes

Uso dos módulos


---

# 6. Indicadores Principais

Mostrar:

## Empresas


Total cadastradas

Ativas

Bloqueadas

Teste


---

## Usuários


Total usuários

Usuários ativos

Últimos acessos


---

## Sistema


Produtos cadastrados

Vendas realizadas

Volume financeiro processado


---

# 7. Gestão de Empresas (Tenants)

Tela:


Empresas

[ Nova Empresa ]

Nome

Plano

Usuários

Status

Criada em

Ações


---

# 8. Cadastro de Empresa

Campos:


Razão social

Nome fantasia

Documento

Email

Telefone

Plano

Status

Data início


---

Exemplo:


Empresa:

Importadora João

Plano:

Profissional

Status:

Ativo


---

# 9. Status da Empresa

Estados:


TRIAL

ACTIVE

SUSPENDED

BLOCKED

CANCELED


---

## TRIAL

Período de avaliação.

---

## ACTIVE

Cliente ativo.

---

## SUSPENDED

Suspensão temporária.

---

## BLOCKED

Bloqueio administrativo.

---

# 10. Acesso à Empresa

O desenvolvedor poderá:


Entrar como administrador da empresa


---

Objetivo:

- suporte;
- manutenção;
- correção de problemas.

---

Registrar:


Impersonation Log


---

Exemplo:


Administrador SaaS

Acessou empresa X

Motivo:

Suporte técnico


---

# 11. Gestão de Planos SaaS

Criar módulo:


Planos


---

Exemplo:

## Plano Básico


Usuários:

3

Produtos:

500

Preço:

R$49/mês


---

## Plano Profissional


Usuários:

10

Produtos:

5000

Preço:

R$99/mês


---

# 12. Estrutura de Plano

Tabela:


plans


Campos:


id

name

price

billing_cycle

max_users

max_products

max_storage

features

status


---

# 13. Controle de Recursos

Cada plano poderá limitar:


Quantidade usuários

Quantidade produtos

Espaço arquivos

Quantidade vendas

Módulos disponíveis


---

Exemplo:

Empresa no plano básico:


Limite produtos:

500


Tentativa:


Cadastrar 501º produto


Sistema bloqueia.

---

# 14. Controle de Assinaturas

Tabela:


subscriptions


Campos:


tenant_id

plan_id

start_date

end_date

status

payment_status


---

Estados:


ACTIVE

PENDING

EXPIRED

CANCELED


---

# 15. Gestão de Usuários Globais

O desenvolvedor poderá visualizar:


Todos usuários

Empresa vinculada

Último acesso

Status


---

Filtros:


Empresa

Nome

Email

Status


---

# 16. Bloqueio de Usuários

Permitir:


Ativar

Bloquear

Desativar


---

Motivos:


Solicitação cliente

Segurança

Inadimplência

Problema técnico


---

# 17. Gestão de Permissões Globais

O desenvolvedor poderá criar:


Módulos

Permissões

Perfis padrão


---

Exemplo:

Criar permissão:


reports.export


---

# 18. Configurações Globais

Tela:


Configurações do Sistema


---

Permitir:


Nome plataforma

Logo

Email remetente

Parâmetros gerais

Manutenção


---

# 19. Configuração de Email

Preparar integração:


SMTP


---

Dados:


Servidor

Porta

Usuário

Senha

Remetente


---

Usado para:

- recuperação senha;
- notificações;
- avisos.

---

# 20. Gestão de Arquivos

Monitorar:


Espaço utilizado

Arquivos enviados

Limites por empresa


---

Exemplo:


Empresa X

Uso:

850MB / 1GB


---

# 21. Monitoramento de Uso

Coletar métricas:


Quantidade produtos

Quantidade vendas

Usuários ativos

Acessos


---

Objetivo:

Entender utilização do SaaS.

---

# 22. Logs Globais

Visualizar:


Todos eventos importantes


---

Filtros:


Empresa

Usuário

Módulo

Data

Ação


---

Exemplo:


Empresa:

ABC Importações

Evento:

Alteração de preço

Usuário:

Carlos

Data:

01/08/2026


---

# 23. Auditoria Administrativa

Criar tabela:


admin_logs


---

Campos:


id

admin_id

action

target_type

target_id

description

ip

created_at


---

# 24. Sistema de Suporte

Criar estrutura futura:


Tickets


---

Permitir:

Empresa abre chamado.

↓

Administrador responde.

---

Campos:


Empresa

Assunto

Mensagem

Status

Prioridade


---

# 25. Status dos Tickets


OPEN

IN_PROGRESS

WAITING

RESOLVED

CLOSED


---

# 26. Notificações Administrativas

Criar alertas:

Exemplo:


Nova empresa cadastrada

Plano expirando

Erro crítico

Servidor com problema


---

# 27. Gestão de Manutenção

Permitir:

Ativar:


Modo manutenção


---

Mensagem:


Sistema temporariamente indisponível.
Voltaremos em breve.


---

# 28. Página de Status

Futura implementação:


status.importcontrol.com


---

Mostrar:


Sistema operacional

Banco

API

Serviços


---

# 29. Relatórios SaaS

Criar relatórios:

## Crescimento


Novas empresas por mês


---

## Receita


Assinaturas

Faturamento


---

## Retenção


Cancelamentos

Ativações


---

# 30. Métrica de Clientes

Indicadores:

## MRR

Receita recorrente mensal.

---

## Churn

Cancelamentos.

---

## LTV

Valor médio cliente.

---

## CAC

Custo aquisição cliente.

---

# 31. Banco de Dados Adicional

## admin_users

```sql
id

name

email

password

status

created_at
subscriptions
id

tenant_id

plan_id

status

start_date

end_date
plans
id

name

price

features

limits

status
support_tickets
id

tenant_id

subject

message

priority

status

created_at
system_settings
id

key

value

type
32. Serviços Backend

Criar:

TenantService

PlanService

SubscriptionService

AdminService

SupportService

MetricsService
33. Controllers

Criar:

AdminDashboardController

TenantController

PlanController

SubscriptionController

SupportController

SystemController
34. Permissões Exclusivas

Criar:

admin.tenants

admin.users

admin.plans

admin.billing

admin.logs

admin.settings

admin.support
35. Segurança Especial

O SUPER_ADMIN deverá possuir:

autenticação reforçada;
logs obrigatórios;
possibilidade de 2FA;
timeout reduzido.
36. Autenticação em Dois Fatores (2FA)

Preparar:

Google Authenticator

TOTP

Fluxo:

Senha

↓

Código 6 dígitos

↓

Acesso liberado
37. Backup Administrativo

Permitir:

Executar backup manual

Visualizar último backup

Restaurar ambiente teste
38. Critérios de Aceitação
[ ] Dashboard SaaS

[ ] Gestão empresas

[ ] Gestão planos

[ ] Gestão assinaturas

[ ] Gestão usuários

[ ] Métricas

[ ] Logs globais

[ ] Suporte

[ ] Configurações

[ ] Segurança reforçada
Encerramento da Parte 19

O módulo administrativo transforma o ImportControl de um sistema interno em uma verdadeira plataforma SaaS.

Com ele, o desenvolvedor terá controle completo sobre:

clientes;
planos;
crescimento;
segurança;
manutenção;
evolução do produto.