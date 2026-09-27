# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 28 — Plano de Desenvolvimento por Sprint

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Planejamento de desenvolvimento, prioridades, entregas e estratégia de construção do sistema

---

# 1. Objetivo

Esta etapa define a estratégia de desenvolvimento do ImportControl.

O objetivo é construir o sistema de forma organizada, evitando:

- desenvolvimento desordenado;
- retrabalho;
- criação prematura de funcionalidades;
- problemas arquiteturais futuros.

O desenvolvimento deverá seguir uma abordagem:


Base sólida

↓

MVP funcional

↓

Melhorias operacionais

↓

Recursos avançados

↓

Plataforma SaaS completa


---

# 2. Estratégia Geral

O projeto será dividido em:


Fase 1 — Fundação

Fase 2 — MVP Operacional

Fase 3 — Gestão Completa

Fase 4 — Inteligência e BI

Fase 5 — SaaS Comercial


---

# 3. Metodologia

Utilizar metodologia ágil:


Sprints de 2 semanas


Cada sprint deverá possuir:

- objetivo;
- funcionalidades;
- critérios de conclusão;
- testes.

---

# FASE 1 — FUNDAÇÃO DO SISTEMA

---

# Sprint 01 — Configuração Inicial do Projeto

## Objetivo

Criar a base técnica do sistema.

---

## Entregas

Criar:


Projeto PHP 8+

Composer

Autoload PSR-4

Estrutura MVC

Configuração Banco

Sistema de Rotas

Ambiente .env


---

## Estrutura inicial:


app/

config/

core/

public/

resources/

storage/

database/


---

## Implementar:

- conexão PDO;
- tratamento de erros;
- logs básicos;
- página inicial.

---

## Critério de conclusão


[ ] Projeto abre

[ ] Banco conecta

[ ] Rotas funcionando

[ ] Estrutura MVC pronta


---

# Sprint 02 — Autenticação e Segurança

## Objetivo

Criar controle de acesso.

---

## Implementar:

Login:


Email

Senha


---

Funcionalidades:


Login

Logout

Sessão

Recuperação senha

Controle usuário


---

Criar:


users

roles

permissions


---

## Critério:


[ ] Usuário consegue entrar

[ ] Sessão segura

[ ] Permissões funcionando


---

# Sprint 03 — Estrutura SaaS Multi-Tenant

## Objetivo

Preparar sistema para múltiplas empresas.

---

Criar:


tenants


---

Implementar:


Empresa

Usuário vinculado

Separação dados


---

Regra:

Toda consulta deve considerar:

```sql
tenant_id
Critério:
[ ] Empresas isoladas

[ ] Usuário vê apenas sua empresa
FASE 2 — MVP OPERACIONAL
Sprint 04 — Cadastros Básicos
Objetivo

Criar base operacional.

Implementar:

Produtos
Cadastro

Categorias

Fornecedores

Custos
Clientes
Cadastro

Histórico
Critério:
[ ] Produto cadastrado

[ ] Cliente cadastrado

[ ] Fornecedor cadastrado
Sprint 05 — Controle de Estoque
Objetivo

Controlar mercadorias.

Implementar:

Entrada estoque

Saída estoque

Ajustes

Movimentações

Criar:

stock

stock_movements
Critério:
[ ] Estoque atualiza

[ ] Histórico funciona
Sprint 06 — Cadastro de Viagens
Objetivo

Controlar importações.

Implementar:

Criar viagem

Destino

Datas

Cotação

Despesas

Criar:

trips

trip_expenses
Critério:
[ ] Viagem cadastrada

[ ] Despesas convertidas
Sprint 07 — Compras Internacionais
Objetivo

Controlar aquisição dos produtos.

Implementar:

Compra internacional

Produtos comprados

Conversão moeda

Custo real

Criar:

purchases

purchase_items
Critério:
[ ] Compra registrada

[ ] Produto recebe custo
Sprint 08 — Sistema de Vendas
Objetivo

Criar operação comercial.

Implementar:

Nova venda

Adicionar produtos

Alterar preço

Desconto

Finalização

Integração:

Venda

↓

Baixa estoque

↓

Financeiro
Critério:
[ ] Venda concluída

[ ] Estoque atualizado

[ ] Lucro calculado
FASE 3 — GESTÃO COMPLETA
Sprint 09 — Financeiro Básico
Objetivo

Controlar dinheiro.

Implementar:

Receitas

Despesas

Contas pagar

Contas receber

Criar:

financial_transactions

accounts_payable

accounts_receivable
Sprint 10 — Fluxo de Caixa
Objetivo

Visualizar saúde financeira.

Implementar:

Saldo

Entradas

Saídas

Resultado

Relatórios:

Diário

Mensal

Anual
Sprint 11 — Relatórios Operacionais
Objetivo

Gerar informações.

Criar:

Relatório produtos

Relatório vendas

Relatório clientes

Relatório estoque

Exportação:

PDF

Excel
Sprint 12 — Dashboard Inicial
Objetivo

Criar visão executiva.

Cards:

Faturamento

Lucro

Estoque

Clientes

Vendas

Gráficos:

Chart.js
FASE 4 — INTELIGÊNCIA E BI
Sprint 13 — Indicadores Avançados

Implementar:

ROI viagens

Margem produtos

Ticket médio

Produtos rentáveis
Sprint 14 — Análises Estratégicas

Criar:

Ranking clientes

Ranking produtos

Produtos parados

Previsões
Sprint 15 — Automações

Implementar:

Alertas

Notificações

Relatórios automáticos

Exemplos:

Produto sem estoque

Conta vencendo

Cliente inadimplente
FASE 5 — SAAS COMERCIAL
Sprint 16 — Painel Administrador

Objetivo:

Gerenciar plataforma.

Criar:

Empresas cadastradas

Usuários

Status assinatura

Logs
Sprint 17 — Planos e Assinaturas

Preparar:

Plano gratuito

Plano básico

Plano profissional

Controle:

Limites

Recursos liberados

Expiração
Sprint 18 — Pagamentos SaaS

Integração futura:

Mercado Pago

Stripe

Pagamentos recorrentes
Sprint 19 — API Pública

Criar:

API REST

Tokens

Documentação

Possibilitar:

Aplicativo mobile

Integrações externas
Sprint 20 — Otimização e Produção

Objetivo:

Preparar lançamento.

Executar:

Testes

Segurança

Performance

Backup

Monitoramento
4. Ordem Prioritária do MVP

O MVP mínimo deverá conter:

Login

Empresa

Produtos

Clientes

Estoque

Viagens

Compras

Vendas

Financeiro básico
5. Funcionalidades Pós-MVP

Após validação:

Dashboard avançado

BI

Automação

IA

Aplicativo mobile

Integrações
6. Estratégia de Desenvolvimento com GitHub Copilot

O Copilot deverá ser utilizado módulo por módulo.

Sempre fornecer contexto:

Exemplo:

Você está desenvolvendo o módulo Produto.

Siga arquitetura MVC.

Use Service Layer.

Não coloque regra no Controller.

Utilize Repository.

PHP 8.2.

Código limpo.
7. Padrão de Desenvolvimento por Feature

Cada funcionalidade deverá seguir:

1. Criar Migration

↓

2. Criar Model

↓

3. Criar Repository

↓

4. Criar Service

↓

5. Criar Controller

↓

6. Criar Views

↓

7. Criar Javascript

↓

8. Testar
8. Controle de Versões

Branches:

main

develop

feature/produtos

feature/vendas

feature/financeiro
9. Processo de Commit

Exemplos:

feat: cria cadastro de produtos

fix: corrige cálculo estoque

refactor: melhora ProductService

security: adiciona validação csrf
10. Ambiente de Desenvolvimento

Recomendado:

Visual Studio Code

PHP 8.2

Composer

MariaDB

Git

GitHub Copilot Pro
11. Ambiente de Produção

Servidor:

Linux

Apache/Nginx

PHP 8+

MariaDB

SSL

Backup automático
12. Testes Antes de Cada Release

Checklist:

[ ] Login

[ ] Permissões

[ ] Cadastro

[ ] Cálculos

[ ] Estoque

[ ] Financeiro

[ ] Segurança

[ ] Backup
13. Cronograma Estimado

Desenvolvimento individual:

MVP:

3 a 5 meses

Produto SaaS completo:

8 a 12 meses

Equipe pequena:

4 a 6 meses
14. Critérios de Sucesso

O projeto será considerado bem sucedido quando:

Usuário consegue controlar toda operação

Custos reais são conhecidos

Lucro é calculado automaticamente

Sistema suporta múltiplas empresas

Código permite evolução contínua
Encerramento da Parte 28

O plano definido permite construir o ImportControl de forma profissional.

A estratégia evita criar um sistema grande e desorganizado, priorizando:

Base correta

↓

MVP funcionando

↓

Validação do negócio

↓

Escala SaaS