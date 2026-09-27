# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 24 — Módulo Financeiro e Gestão de Fluxo de Caixa

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação funcional do módulo financeiro, receitas, despesas, fluxo de caixa e análise de resultados

---

# 1. Objetivo

O módulo Financeiro será responsável pelo controle completo das movimentações financeiras do negócio.

O objetivo é permitir que o proprietário acompanhe:

- quanto dinheiro entrou;
- quanto dinheiro saiu;
- despesas operacionais;
- investimentos em mercadorias;
- valores a receber;
- valores a pagar;
- lucro real;
- desempenho financeiro do negócio.

---

# 2. Conceito do Módulo

O fluxo financeiro do negócio será:


Viagem

↓

Compra de mercadorias

↓

Despesas operacionais

↓

Venda dos produtos

↓

Recebimentos

↓

Resultado financeiro

↓

Lucro real


---

# 3. Objetivo Gerencial

O sistema deverá responder:

- Quanto foi investido?
- Quanto foi vendido?
- Quanto tenho para receber?
- Quanto devo pagar?
- Qual meu lucro real?
- Qual período foi mais lucrativo?
- Quais despesas consomem mais recursos?

---

# 4. Menu Financeiro

Estrutura:


Financeiro

├── Dashboard financeiro

├── Contas a pagar

├── Contas a receber

├── Fluxo de caixa

├── Receitas

├── Despesas

├── Categorias financeiras

└── Relatórios


---

# 5. Dashboard Financeiro

Tela principal:


Financeiro

Saldo atual

Receitas do mês

Despesas do mês

Lucro líquido

Gráficos:

Entrada x Saída

Evolução mensal

Categorias de gastos


---

# 6. Indicadores Principais

Mostrar:

## Receita Total

Soma de:


Todas as vendas pagas


---

## Despesas Totais

Soma de:


Todas as despesas pagas


---

## Resultado

Fórmula:


Receita - Despesa


---

## Margem de Lucro

Fórmula:


Lucro ÷ Receita × 100


---

# 7. Conceito de Movimentação Financeira

Toda movimentação deverá possuir:


Entrada

ou

Saída


---

Exemplo:

Entrada:


Venda de produto

+R$2.000


---

Saída:


Pagamento hotel

-R$500


---

# 8. Lançamento Financeiro Manual

Permitir cadastrar:


Tipo

Categoria

Descrição

Valor

Data

Forma pagamento

Conta

Observação


---

Exemplo:


Tipo:

Despesa

Descrição:

Combustível

Valor:

R$300


---

# 9. Categorias Financeiras

Criar categorias padrão.

## Receitas


Venda de produtos

Serviços

Outros recebimentos


---

## Despesas


Viagem

Transporte

Alimentação

Hospedagem

Marketing

Impostos

Taxas

Outros


---

# 10. Tabela Financeira

Criar:


financial_categories


Campos:

```sql
id

tenant_id

name

type

parent_id

status

created_at
11. Contas a Receber

Responsável por controlar:

Valores que o cliente deve pagar

Origem:

Venda parcelada

Venda fiada

Cobrança manual
12. Cadastro de Conta a Receber

Campos:

Cliente

Descrição

Valor

Data vencimento

Forma pagamento

Status

Exemplo:

Cliente:

João


Valor:

R$3.000


Vencimento:

10/09/2026
13. Status Contas a Receber

Estados:

PENDING

PAID

OVERDUE

CANCELED
PENDING

Aguardando pagamento.

PAID

Recebido.

OVERDUE

Atrasado.

14. Controle de Inadimplência

Dashboard:

Mostrar:

Clientes inadimplentes

Valor pendente

Dias atraso

Exemplo:

Cliente:

Carlos


Débito:

R$1.500


Atraso:

20 dias
15. Contas a Pagar

Responsável por:

Obrigações financeiras

Exemplos:

Fornecedor

Cartão

Aluguel

Internet

Viagem

Impostos
16. Cadastro de Conta a Pagar

Campos:

Fornecedor

Categoria

Descrição

Valor

Vencimento

Pagamento

Status
17. Status Contas a Pagar
PENDING

PAID

OVERDUE

CANCELED
18. Fluxo de Caixa

Objetivo:

Mostrar movimentação financeira no tempo.

Modelo:

Dia 01

Entrada:

R$5.000


Saída:

R$2.000


Saldo:

R$3.000
19. Visões do Fluxo de Caixa

Permitir:

Diário

Semanal

Mensal

Anual
20. Saldo Financeiro

Fórmula:

Saldo anterior

+

Entradas

-

Saídas

=

Saldo atual
21. Contas Bancárias

Permitir cadastrar:

Banco

Conta

Carteira

Dinheiro físico

Exemplo:

Banco:

Nubank


Saldo:

R$25.000
22. Tabela Accounts
id

tenant_id

name

bank

type

balance

status

created_at
23. Transferências Internas

Permitir:

Transferir valores entre contas

Exemplo:

Nubank

↓

Carteira dinheiro

R$1.000
24. Conciliação Financeira

Objetivo:

Comparar:

Sistema

x

Banco

Exemplo:

Banco:

R$10.000

Sistema:

R$9.500

Identificar diferença.

25. Integração Futura Bancária

Preparar:

Open Finance

Possibilidades:

importar extrato;
conciliar automaticamente;
identificar pagamentos.
26. Controle de Compras

Toda compra internacional poderá gerar:

Conta a pagar

Fluxo:

Compra registrada

↓

Valor convertido

↓

Financeiro atualizado

↓

Pagamento registrado
27. Despesas de Viagem Integradas

As despesas cadastradas no módulo viagem poderão alimentar:

Financeiro automaticamente

Exemplo:

Hotel:

USD500

Conversão:

R$2.700

Criar lançamento:

Despesa viagem

-R$2.700
28. Lucro Real

O sistema deverá calcular:

Receita vendas

-

Custo produtos vendidos

-

Despesas

=

Lucro real

Exemplo:

Vendas:

R$100.000

Custos:

R$60.000

Despesas:

R$15.000

Lucro:

R$25.000
29. Demonstrativo de Resultado

Criar relatório:

DRE Simplificada

Estrutura:

Receita Bruta

(-) Custos

(-) Despesas

= Resultado Líquido
30. Análise por Produto

Mostrar:

Produto

Quantidade vendida

Receita

Custo

Lucro

Exemplo:

AirPods


Venda:

R$50.000


Lucro:

R$15.000
31. Análise por Viagem

Permitir:

Quanto cada viagem gerou de retorno

Exemplo:

Viagem:

Miami Julho

Investimento:

R$80.000

Retorno:

R$140.000

Lucro:

R$60.000
32. Relatórios Financeiros

Criar:

Relatório de receitas
Período

Venda

Cliente

Valor
Relatório de despesas
Categoria

Valor

Data
Relatório de lucro
Receita

Custos

Despesas

Resultado
33. Exportações

Permitir:

PDF

Excel

CSV
34. Alertas Financeiros

Criar notificações:

Conta vencendo

Cliente atrasado

Saldo baixo

Despesa elevada
35. Regras de Negócio
Regra 1

Venda confirmada gera receita.

Regra 2

Pagamento recebido altera saldo.

Regra 3

Despesa paga reduz saldo.

Regra 4

Cancelamento deve estornar movimentação.

Regra 5

Movimentações financeiras nunca devem ser apagadas.

36. Auditoria Financeira

Registrar:

criação;
alteração;
exclusão lógica;
pagamento;
cancelamento.
37. Banco de Dados
financial_transactions
id

tenant_id

type

category_id

description

amount

currency_id

exchange_rate

account_id

reference_type

reference_id

transaction_date

created_at
accounts_receivable
id

tenant_id

customer_id

sale_id

amount

due_date

paid_at

status
accounts_payable
id

tenant_id

supplier_id

description

amount

due_date

paid_at

status
financial_accounts
id

tenant_id

name

type

balance

status
38. Services Backend

Criar:

FinancialService

CashFlowService

ReceivableService

PayableService

ProfitService

ReportFinancialService
39. Controllers

Criar:

FinancialController

CashFlowController

ReceivableController

PayableController

ReportFinancialController
40. Critérios de Aceitação
[ ] Dashboard financeiro

[ ] Receitas

[ ] Despesas

[ ] Contas a pagar

[ ] Contas a receber

[ ] Fluxo de caixa

[ ] Controle contas bancárias

[ ] Lucro real

[ ] DRE

[ ] Relatórios

[ ] Exportações

[ ] Auditoria
Encerramento da Parte 24

O módulo Financeiro transforma o ImportControl em uma ferramenta completa de gestão empresarial.

Ele conecta:

Viagens

↓

Compras

↓

Produtos

↓

Vendas

↓

Recebimentos

↓

Lucro Real

Com esse módulo, o proprietário deixa de apenas controlar vendas e passa a administrar a saúde financeira completa do negócio.