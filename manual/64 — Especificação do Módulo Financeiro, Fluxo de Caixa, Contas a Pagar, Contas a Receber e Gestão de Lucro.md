# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 64 — Especificação do Módulo Financeiro, Fluxo de Caixa, Contas a Pagar, Contas a Receber e Gestão de Lucro

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Controle financeiro completo, movimentações, fluxo de caixa, despesas, receitas, pagamentos e análise de rentabilidade

---

# 1. Objetivo

O módulo Financeiro será responsável por controlar toda movimentação monetária da empresa.

O objetivo é permitir que o proprietário tenha uma visão clara de:


Quanto dinheiro entrou?

Quanto dinheiro saiu?

Qual é o lucro real?

Quais despesas existem?

Quais clientes possuem valores pendentes?

Quais fornecedores precisam ser pagos?

Qual importação trouxe maior retorno?


---

# 2. Conceito Geral

O fluxo financeiro será:


Venda realizada

    ↓

Receita registrada

    ↓

Pagamento recebido

    ↓

Fluxo de caixa atualizado

Compra/importação

    ↓

Despesa registrada

    ↓

Pagamento realizado

    ↓

Fluxo financeiro atualizado

    ↓

Relatórios e indicadores


---

# 3. Requisitos Funcionais

O módulo deverá permitir:


RF001 - Registrar receitas

RF002 - Registrar despesas

RF003 - Controlar contas a pagar

RF004 - Controlar contas a receber

RF005 - Controlar fluxo de caixa

RF006 - Gerar relatórios financeiros

RF007 - Calcular lucro líquido

RF008 - Controlar categorias financeiras

RF009 - Realizar conciliação

RF010 - Exportar informações


---

# 4. Conceito Financeiro

O sistema deverá separar:

## Receitas

Valores recebidos:


Venda de produtos

Serviços

Outras entradas


---

## Despesas

Valores pagos:


Compra produtos

Importações

Passagens

Hospedagem

Marketing

Operacionais


---

# 5. Estrutura Financeira

Modelo:


Receitas

↓

Faturamento

↓

Custos

↓

Despesas

↓

Lucro

↓

Resultado final


---

# 6. Categorias Financeiras

Permitir classificação:

## Receitas


Venda produtos

Pagamento cliente

Outros recebimentos


---

## Despesas


Importação

Transporte

Marketing

Funcionários

Impostos

Operacional


---

# 7. Tabela Financial Categories

```sql
CREATE TABLE financial_categories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

type VARCHAR(30),

name VARCHAR(100),

description TEXT,

status VARCHAR(30),

created_at TIMESTAMP,

updated_at TIMESTAMP

);
8. Tipos de Movimentação

O sistema deverá possuir:

INCOME

Receita


EXPENSE

Despesa
9. Tabela Financial Transactions

Tabela principal financeira.

CREATE TABLE financial_transactions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

category_id BIGINT,

type VARCHAR(30),

description VARCHAR(255),

amount DECIMAL(12,2),

transaction_date DATE,

payment_date DATE,

reference_type VARCHAR(50),

reference_id BIGINT,

status VARCHAR(30),

created_at TIMESTAMP,

updated_at TIMESTAMP

);
10. Origem das Movimentações

Toda movimentação poderá ter origem:

Venda

Importação

Compra

Despesa manual

Pagamento

Ajuste
11. Contas a Receber

Responsável por controlar valores futuros.

Exemplo:

Venda parcelada

Cliente:

João


Total:

R$5.000


Parcelas:

5x R$1.000
12. Tabela Accounts Receivable
CREATE TABLE accounts_receivable (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

customer_id BIGINT,

sale_id BIGINT,

description VARCHAR(255),

amount DECIMAL(12,2),

due_date DATE,

payment_date DATE,

status VARCHAR(30),

created_at TIMESTAMP

);
13. Status Contas Receber

Estados:

PENDING

Pendente


PAID

Pago


OVERDUE

Atrasado


CANCELLED

Cancelado
14. Controle de Inadimplência

O sistema deverá identificar:

Clientes atrasados

Dias de atraso

Valor pendente

Histórico pagamentos
15. Contas a Pagar

Controlar obrigações:

Fornecedores

Despesas viagem

Serviços

Operações
16. Tabela Accounts Payable
CREATE TABLE accounts_payable (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

supplier_id BIGINT,

description VARCHAR(255),

amount DECIMAL(12,2),

due_date DATE,

payment_date DATE,

status VARCHAR(30),

created_at TIMESTAMP

);
17. Fluxo de Caixa

O sistema deverá apresentar:

Saldo inicial

Entradas

Saídas

Saldo final
18. Fórmula Fluxo Caixa
Saldo final

=

Saldo inicial

+

Entradas

-

Saídas
19. Tabela Cash Flow
CREATE TABLE cash_flow (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

reference_date DATE,

initial_balance DECIMAL(12,2),

income_total DECIMAL(12,2),

expense_total DECIMAL(12,2),

final_balance DECIMAL(12,2),

created_at TIMESTAMP

);
20. Dashboard Financeiro

Exibir:

Saldo atual

Receitas mês

Despesas mês

Lucro mês

Contas vencendo

Contas atrasadas

Margem líquida
21. Demonstrativo de Resultado (DRE)

Criar visão:

Receita bruta

(-) Custos produtos

(-) Despesas operacionais

= Lucro operacional

(-) Outras despesas

= Lucro líquido
22. Cálculo Lucro Real

Fórmula:

Lucro líquido

=

Vendas

-

Custo mercadorias

-

Despesas

Exemplo:

Venda:

R$100.000


Produtos:

R$50.000


Despesas:

R$20.000


Lucro:

R$30.000
23. Margem de Lucro

Fórmula:

Margem %

=

Lucro líquido

/

Receita total

×100
24. Controle por Importação

O sistema deverá mostrar:

Valor investido

Custos

Vendas geradas

Lucro

ROI
25. Controle por Produto

Mostrar:

Produto

Custo

Venda

Lucro unitário

Lucro acumulado
26. Conciliação Financeira

Preparar integração futura:

Banco

Carteiras digitais

Maquininhas

PIX
27. Importação de Extratos

Preparar:

CSV

OFX

Integrações API bancária
28. Controle de Caixa Diário

Permitir:

Abertura caixa

Entradas

Saídas

Fechamento

Diferença
29. Tabela Cash Registers
CREATE TABLE cash_registers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

opening_balance DECIMAL(12,2),

closing_balance DECIMAL(12,2),

opened_at DATETIME,

closed_at DATETIME,

status VARCHAR(30)

);
30. Movimentações do Caixa
CREATE TABLE cash_movements (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

cash_register_id BIGINT,

type VARCHAR(30),

description VARCHAR(255),

amount DECIMAL(12,2),

created_at TIMESTAMP

);
31. Relatórios Financeiros

Criar:

Fluxo caixa

DRE

Receitas período

Despesas período

Lucro por produto

Lucro por importação

Contas vencidas
32. Filtros Financeiros

Permitir:

Período

Categoria

Produto

Importação

Cliente

Fornecedor
33. Alertas Financeiros

Criar avisos:

Conta vencendo amanhã

Cliente inadimplente

Despesa acima média

Fluxo negativo
34. Serviços Backend

Criar:

FinancialService

CashFlowService

AccountsReceivableService

AccountsPayableService

ProfitService

FinancialReportService
35. Controllers

Criar:

FinancialController

CashFlowController

ReceivableController

PayableController

FinancialReportController
36. API Financeiro

Endpoints:

GET /api/v1/financial

POST /api/v1/financial

GET /api/v1/cash-flow

GET /api/v1/accounts-receivable

GET /api/v1/accounts-payable

GET /api/v1/financial/reports
37. Permissões

Criar:

financial.view

financial.create

financial.edit

financial.delete

financial.export

financial.reports
38. Auditoria

Registrar:

Criação despesa

Alteração valor

Pagamento realizado

Cancelamento

Exportação relatório
39. Regras de Negócio
Regra 1

Toda receita deve possuir origem.

Regra 2

Toda despesa deve possuir categoria.

Regra 3

Movimentações financeiras não devem ser apagadas.

Regra 4

Alterações financeiras devem gerar histórico.

Regra 5

Lucro deve considerar custo real do produto.

40. Critérios de Aceitação
[ ] Receitas funcionando

[ ] Despesas funcionando

[ ] Contas receber funcionando

[ ] Contas pagar funcionando

[ ] Fluxo caixa funcionando

[ ] DRE funcionando

[ ] Lucro calculado

[ ] Relatórios funcionando

[ ] Alertas funcionando

[ ] Auditoria funcionando
Encerramento da Parte 64

O módulo Financeiro será responsável por transformar os dados operacionais do ImportControl em uma visão real da saúde financeira do negócio.

O proprietário poderá responder:

Quanto realmente estou lucrando?

Qual produto dá mais retorno?

Quanto investi na importação?

Tenho dinheiro disponível?

Quais despesas estão comprometendo meu negócio?

Este módulo será fundamental para transformar o sistema em uma ferramenta de decisão empresarial.