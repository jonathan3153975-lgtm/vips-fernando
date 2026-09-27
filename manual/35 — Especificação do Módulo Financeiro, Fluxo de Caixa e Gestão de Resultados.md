# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 35 — Especificação do Módulo Financeiro, Fluxo de Caixa e Gestão de Resultados

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Controle financeiro, receitas, despesas, fluxo de caixa, indicadores e análise de lucratividade

---

# 1. Objetivo

O módulo Financeiro será responsável por consolidar todas as movimentações financeiras do negócio.

Ele deverá permitir que o proprietário tenha uma visão clara de:


Quanto dinheiro entrou

Quanto dinheiro saiu

Quanto está disponível

Quanto foi investido

Quanto cada viagem gerou

Qual o lucro real do negócio


---

# 2. Conceito Financeiro

O sistema deverá diferenciar:

## Fluxo de caixa

Movimentação real de dinheiro.

Exemplo:


Venda recebida

↓

Dinheiro entra


---

## Resultado econômico

Lucro da operação.

Exemplo:


Venda

Custo produto

Despesas

=

Lucro


---

# 3. Objetivos do Módulo

Permitir:


Registrar receitas

Controlar despesas

Gerenciar contas a pagar

Gerenciar contas a receber

Controlar caixa

Analisar lucro

Gerar relatórios


---

# 4. Menu Financeiro

Estrutura:


Financeiro

├── Dashboard financeiro

├── Fluxo de caixa

├── Contas a pagar

├── Contas a receber

├── Receitas

├── Despesas

├── Categorias financeiras

├── Contas bancárias

└── Relatórios


---

# 5. Dashboard Financeiro

Tela principal.

Exibir:


Saldo atual

Receitas do mês

Despesas do mês

Lucro estimado

Contas vencendo

Contas atrasadas


---

# 6. Indicadores Principais

Criar cards:


Faturamento

Lucro bruto

Lucro líquido

Margem %

Ticket médio

Estoque investido


---

# 7. Estrutura Financeira

O sistema trabalhará com:


Movimentações

↓

Categorias

↓

Contas

↓

Relatórios


---

# 8. Contas Financeiras

Permitir cadastrar:

Exemplos:


Banco principal

Carteira dinheiro

Conta internacional

Cartão de crédito

PIX


---

Tabela:

## financial_accounts

```sql
id

tenant_id

name

type

bank_name

agency

account_number

balance

status

created_at
9. Tipos de Conta
BANK

Conta bancária


CASH

Dinheiro físico


CREDIT_CARD

Cartão


DIGITAL

Carteira digital


INTERNATIONAL

Conta exterior
10. Receitas

Toda entrada financeira.

Exemplos:

Venda

Recebimento parcelado

Outros ganhos

Tabela:

financial_income
id

tenant_id

category_id

customer_id

sale_id

description

amount

payment_date

account_id

created_at
11. Integração com Vendas

Quando uma venda for concluída:

Venda

↓

Gerar receita financeira

Exemplo:

Venda:

R$5.000

Sistema cria:

Receita:

Venda de produtos

Valor:

R$5.000
12. Despesas

Controlar:

Custos operacionais

Viagens

Compras

Impostos

Serviços

Outros gastos

Tabela:

financial_expenses
id

tenant_id

category_id

supplier_id

trip_id

description

amount

payment_date

account_id

status

created_at
13. Categorias de Despesas

Exemplos:

Viagem

Mercadoria

Frete

Impostos

Marketing

Sistema

Aluguel

Combustível

Outros

Tabela:

financial_categories
id

tenant_id

name

type

color

created_at
14. Contas a Pagar

Controlar obrigações futuras.

Exemplo:

Fornecedor

Vencimento

Valor

Status

Tabela:

accounts_payable
id

tenant_id

supplier_id

description

amount

due_date

payment_date

status

created_at
15. Status Contas a Pagar
PENDING

Pendente


PAID

Pago


OVERDUE

Atrasado


CANCELLED

Cancelado
16. Contas a Receber

Controlar vendas parceladas.

Exemplo:

Venda:

R$6.000

3 parcelas

Sistema cria:

Parcela 1

Parcela 2

Parcela 3

Tabela:

accounts_receivable
id

tenant_id

customer_id

sale_id

amount

due_date

payment_date

status

created_at
17. Fluxo de Caixa

Representação:

Saldo inicial

+

Entradas

-

Saídas

=

Saldo final
18. Tela Fluxo de Caixa

Layout:

Fluxo de Caixa


Período:


Entradas

+ R$


Saídas

- R$


Saldo

R$
19. Movimentação Financeira Unificada

Criar tabela central:

financial_transactions
id

tenant_id

type

category_id

reference_type

reference_id

description

amount

transaction_date

account_id

user_id

created_at

Tipos:

INCOME

EXPENSE

TRANSFER
20. Transferência Entre Contas

Permitir:

Exemplo:

Banco

↓

Carteira dinheiro

Criar:

Saída conta origem

+

Entrada conta destino
21. Controle de Moedas

Assim como produtos e viagens:

Permitir:

Real

Dólar

Euro

Outras moedas

Campos:

currency_id

foreign_amount

exchange_rate

converted_amount
22. Gastos em Dólar

Exemplo:

Hotel:

USD 500


Cotação:

5,20


Convertido:

R$2.600
23. Custos de Importação

Integrar:

Compra internacional

+

Despesa viagem

+

Taxas

=

Custo total importação
24. Lucro Real

O sistema deverá calcular:

Receita total

-

Custo produtos vendidos

-

Despesas operacionais

=

Lucro líquido
25. Margem de Lucro

Fórmula:

Lucro líquido

÷

Receita total

×

100

Exemplo:

Receita:

R$50.000


Lucro:

R$15.000


Margem:

30%
26. Resultado por Viagem

Permitir analisar:

Viagem Miami


Investimento:

R$30.000


Vendas:

R$60.000


Lucro:

R$20.000
27. Resultado por Produto

Mostrar:

Produto

Quantidade vendida

Receita

Custo

Lucro
28. Resultado por Cliente

Mostrar:

Cliente

Compras

Valor gasto

Margem gerada
29. Controle de Cartão de Crédito

Preparar:

Compras parceladas

Vencimentos

Taxas

Faturas

Tabela futura:

credit_cards
id

tenant_id

name

limit_value

closing_day

due_day
30. Alertas Financeiros

Criar notificações:

Conta vencendo

Conta atrasada

Fluxo negativo

Estoque parado
31. Relatórios Financeiros
Demonstrativo mensal

Mostrar:

Receita

Custos

Despesas

Lucro
Fluxo de caixa

Filtros:

Data

Conta

Categoria
Contas vencidas

Mostrar:

Fornecedor

Valor

Dias atraso
32. Exportações

Permitir:

PDF

Excel

CSV
33. Dashboard Gerencial

Criar visão:

Faturamento mensal

Lucro mensal

Despesas

Produtos rentáveis

Viagens rentáveis

Evolução financeira
34. Banco de Dados Complementar
financial_transactions
id

tenant_id

type

category_id

account_id

reference_type

reference_id

description

amount

transaction_date

created_at
financial_categories
id

tenant_id

name

type

created_at
accounts_receivable
id

tenant_id

sale_id

customer_id

amount

due_date

status
accounts_payable
id

tenant_id

supplier_id

amount

due_date

status
35. Services Backend

Criar:

FinancialService

CashFlowService

IncomeService

ExpenseService

AccountService

ReportFinancialService

ProfitService
36. Controllers

Criar:

FinancialController

CashFlowController

ExpenseController

IncomeController

AccountController

FinancialReportController
37. Regras de Segurança

Controlar:

Quem visualiza lucro

Quem lança despesas

Quem altera valores

Quem exclui movimentações

Exemplo:

Vendedor:

Não acessa financeiro

Administrador:

Acesso total
38. Auditoria

Registrar:

Criação despesa

Alteração valor

Pagamento realizado

Cancelamento

Transferência
39. Funcionalidades Futuras

Preparar:

Integração bancos

Open Finance

Emissão nota fiscal

Integração contabilidade

DRE completo

Dashboard BI
40. Critérios de Aceitação
[ ] Registrar receitas

[ ] Registrar despesas

[ ] Controlar contas pagar

[ ] Controlar contas receber

[ ] Fluxo de caixa funcionando

[ ] Cálculo lucro real

[ ] Controle moedas

[ ] Relatórios financeiros

[ ] Integração vendas

[ ] Integração importações
Encerramento da Parte 35

O módulo Financeiro será responsável por transformar dados operacionais em inteligência de negócio.

O proprietário poderá responder:

Quanto investi?

Quanto vendi?

Quanto ganhei?

Qual viagem trouxe mais retorno?

Qual produto dá mais lucro?

Quanto dinheiro tenho disponível?

Com este módulo, o ImportControl deixa de ser apenas um sistema de controle e passa a ser uma ferramenta de gestão empresarial.