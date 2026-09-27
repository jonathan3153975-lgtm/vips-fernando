# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 14 — Módulo Financeiro, Fluxo de Caixa e Gestão de Custos

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação funcional e técnica do módulo financeiro

---

# 1. Objetivo

O módulo Financeiro será responsável por controlar toda movimentação financeira do negócio, permitindo acompanhar:

- entradas de dinheiro;
- saídas de recursos;
- custos de importação;
- despesas operacionais;
- contas a pagar;
- contas a receber;
- fluxo de caixa;
- lucro real da operação.

---

# 2. Conceito Geral

O módulo financeiro deverá consolidar todas as operações do sistema:


Compras internacionais

↓

Custos de viagem

↓

Produtos

↓

Vendas

↓

Recebimentos

↓

Despesas

↓

Resultado financeiro


---

O objetivo principal é responder:

> "Quanto dinheiro entrou, quanto saiu e quanto realmente estou lucrando?"

---

# 3. Estrutura do Módulo


Financeiro

├── Dashboard Financeiro

├── Contas a Pagar

├── Contas a Receber

├── Fluxo de Caixa

├── Categorias Financeiras

├── Lançamentos

├── Centros de Custos

├── Conciliação

├── Relatórios

└── Configurações


---

# 4. Conceitos Financeiros

O sistema trabalhará com:

## Receita

Entrada de dinheiro.

Exemplos:

- vendas;
- recebimentos;
- outros ganhos.

---

## Despesa

Saída financeira.

Exemplos:

- viagem;
- aluguel;
- taxas;
- fornecedores.

---

## Custo

Valor diretamente ligado ao produto.

Exemplo:

- compra internacional;
- transporte;
- importação.

---

## Investimento

Aplicação de recursos para crescimento.

Exemplo:

- equipamentos;
- ferramentas.

---

# 5. Dashboard Financeiro

Tela inicial deverá apresentar:

## Indicadores principais


Saldo atual

Receita mês

Despesas mês

Lucro líquido

Contas vencendo

Contas atrasadas


---

Exemplo:


Saldo Atual

R$ 85.000

Receita:

R$30.000

Despesas:

R$15.000

Lucro:

R$15.000


---

# 6. Gráficos Financeiros

Utilizar:

Chart.js.

---

## Fluxo de Caixa

Linha temporal:


Janeiro

Fevereiro

Março


---

## Receitas x Despesas

Gráfico comparativo.

---

## Distribuição de Custos

Exemplo:


Viagens 40%

Produtos 45%

Operacional 15%


---

# 7. Plano de Contas

O sistema deverá permitir criar categorias financeiras.

---

Exemplo:

## Receitas


Venda de produtos

Serviços

Outros


---

## Despesas


Viagem

Hotel

Combustível

Marketing

Taxas

Impostos

Outros


---

Tabela:


financial_categories


---

# 8. Categorias Financeiras

Campos:


Nome

Tipo

Categoria pai

Descrição

Status


---

Tipo:


INCOME

EXPENSE

COST

INVESTMENT


---

# 9. Lançamentos Financeiros

Todo movimento financeiro será registrado.

---

Campos:


Tipo

Categoria

Descrição

Valor

Data competência

Data pagamento

Forma pagamento

Status

Origem


---

Exemplo:


Tipo:

Despesa

Categoria:

Hotel

Valor:

R$1.500

Origem:

Viagem Miami 2026


---

# 10. Origem do Lançamento

Todo lançamento deverá possuir origem.

Exemplos:


SALE

TRIP

PURCHASE

MANUAL


---

Objetivo:

Permitir rastreamento.

---

# 11. Contas a Receber

Criadas automaticamente através das vendas.

---

Exemplo:

Venda:


R$6.000

3 parcelas


---

Sistema gera:


Parcela 1

R$2.000

Vencimento 10/08

Parcela 2

R$2.000

Vencimento 10/09

Parcela 3

R$2.000

Vencimento 10/10


---

# 12. Status Contas a Receber

Estados:


PENDING

PAID

OVERDUE

CANCELED


---

# 13. Contas a Pagar

Permitir controlar compromissos.

Exemplos:

- fornecedores;
- aluguel;
- cartão;
- impostos.

---

Campos:


Fornecedor

Categoria

Valor

Vencimento

Pagamento

Status


---

# 14. Alertas Financeiros

Gerar alertas:

## Contas próximas do vencimento

Exemplo:


Conta vence em 3 dias


---

## Contas atrasadas

Exemplo:


Conta vencida há 15 dias


---

# 15. Fluxo de Caixa

O sistema deverá apresentar:


Saldo inicial

Entradas

Saídas

=

Saldo final


---

Exemplo:


Saldo inicial:

R$50.000

Entradas:

R$20.000

Saídas:

R$10.000

Saldo:

R$60.000


---

# 16. Fluxo Diário

Visualização:


01/08

Entrada:

R$5.000

Saída:

R$800

Saldo:
R$4.200


---

# 17. Fluxo Mensal

Agrupar:


Janeiro

Fevereiro

Março


---

Mostrar:

- faturamento;
- custos;
- despesas;
- lucro.

---

# 18. Controle de Custos da Importação

O financeiro deverá identificar:


Produto

Compra

Viagem

Despesa vinculada

Custo final


---

Exemplo:


Importação Miami

Produtos:

R$50.000

Viagem:

R$8.000

Custo total:

R$58.000


---

# 19. Centros de Custos

Permitir separar despesas.

Exemplos:


Importação

Operacional

Marketing

Administrativo


---

Tabela:


cost_centers


---

# 20. Conciliação Financeira

Preparar arquitetura para:

- conferência bancária;
- comparação sistema x banco.

---

Futuro:

Integração API bancária.

---

# 21. Transferências Internas

Permitir:

Exemplo:


Conta Banco

↓

Caixa físico


---

Não deve alterar lucro.

---

# 22. Formas de Pagamento

Cadastro:


Dinheiro

PIX

Cartão

Banco

Carteira


---

Permitir configurar:

- taxas;
- prazo de recebimento.

---

# 23. Taxas Financeiras

Exemplo:

Venda cartão:


R$1.000

Taxa:

3%

Recebido:

R$970


---

Registrar:


Valor bruto

Taxa

Valor líquido


---

# 24. Cálculo de Lucro Real

O sistema deverá considerar:


Receita

Custo Produto

Despesas

Taxas

=

Lucro Real


---

# 25. DRE Simplificada

Gerar relatório:


Receita Bruta

(-) Custos

= Lucro Bruto

(-) Despesas

= Resultado Final


---

# 26. Relatórios Financeiros

## Fluxo de caixa

Filtros:

- período;
- categoria;
- origem.

---

## Receitas

Mostrar:

- vendas;
- pagamentos.

---

## Despesas

Mostrar:

- categoria;
- valor;
- período.

---

## Rentabilidade

Mostrar:

- faturamento;
- custo;
- margem.

---

# 27. Exportações

Permitir:


PDF

Excel

CSV


---

# 28. Banco de Dados

## financial_transactions

```sql
id

tenant_id

type

category_id

description

amount

currency

transaction_date

status

origin_type

origin_id

created_at
accounts_receivable
id

tenant_id

sale_id

amount

due_date

paid_date

status
accounts_payable
id

tenant_id

supplier_id

category_id

amount

due_date

paid_date

status
financial_categories
id

tenant_id

name

type

parent_id

status
29. Serviços Backend

Criar:

FinanceService

CashFlowService

AccountPayableService

AccountReceivableService

ProfitAnalysisService
30. Repositories

Criar:

FinancialRepository

CategoryRepository

ReceivableRepository

PayableRepository
31. Controllers

Criar:

FinanceController

CashFlowController

ReceivableController

PayableController

FinancialReportController
32. Permissões Necessárias

Adicionar:

finance.view

finance.create

finance.edit

finance.delete

finance.payables

finance.receivables

finance.reports
33. Auditoria

Registrar:

criação de lançamento;
alteração de valor;
exclusão;
baixa de pagamento;
cancelamento.
34. Regras de Negócio
Regra 1

Venda confirmada gera receita automaticamente.

Regra 2

Venda parcelada gera contas a receber.

Regra 3

Despesa vinculada à viagem pode compor custo do produto.

Regra 4

Lançamentos financeiros nunca devem ser apagados fisicamente.

Utilizar:

Soft Delete.

Regra 5

Alterações financeiras devem gerar histórico.

35. Critérios de Aceitação
[ ] Dashboard financeiro

[ ] Cadastro categorias

[ ] Lançamentos manuais

[ ] Contas pagar

[ ] Contas receber

[ ] Fluxo caixa

[ ] Relatórios

[ ] Integração com vendas

[ ] Integração com viagens

[ ] Cálculo lucro real

[ ] Auditoria
Encerramento da Parte 14

O módulo financeiro transforma os dados operacionais do ImportControl em inteligência de negócio.

Com ele, o proprietário conseguirá visualizar:

quanto investiu;
quanto vendeu;
quanto recebeu;
quanto gastou;
qual o lucro real da operação.