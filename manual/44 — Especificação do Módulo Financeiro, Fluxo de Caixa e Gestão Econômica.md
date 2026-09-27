# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 44 — Especificação do Módulo Financeiro, Fluxo de Caixa e Gestão Econômica

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Controle financeiro completo, contas a pagar, contas a receber, fluxo de caixa, lucratividade e indicadores econômicos

---

# 1. Objetivo

O módulo Financeiro será responsável por controlar toda movimentação econômica do negócio.

O objetivo é permitir que o proprietário tenha uma visão clara de:


Quanto dinheiro entrou

Quanto dinheiro saiu

Quanto está disponível

Quanto foi investido

Quanto foi vendido

Quanto realmente lucrou


---

# 2. Conceito Geral

O financeiro será integrado com todos os módulos:


Compras internacionais

↓

Custos

↓

Estoque

↓

Vendas

↓

Pagamentos

↓

Fluxo de Caixa

↓

Relatórios


---

# 3. Objetivos Específicos

O módulo deverá permitir:


Controlar receitas

Controlar despesas

Gerenciar contas a pagar

Gerenciar contas a receber

Registrar movimentações

Acompanhar lucro

Analisar rentabilidade

Projetar fluxo futuro


---

# 4. Menu Financeiro

Estrutura:


Financeiro

├── Dashboard financeiro

├── Fluxo de caixa

├── Contas a pagar

├── Contas a receber

├── Movimentações

├── Categorias

├── Contas bancárias

├── Conciliação

├── Relatórios

└── Configurações


---

# 5. Dashboard Financeiro

Exibir:


Saldo atual

Receitas do mês

Despesas do mês

Lucro líquido

Contas vencidas

Valores a receber

Valores a pagar


---

Exemplo:


Saldo atual:

R$80.000

Receitas:

R$50.000

Despesas:

R$20.000

Lucro:

R$30.000


---

# 6. Conceito de Lançamentos Financeiros

Todo evento financeiro será um lançamento.

Exemplos:


Venda realizada

Pagamento fornecedor

Passagem aérea

Hotel

Imposto

Frete

Recebimento cliente


---

# 7. Tipos de Movimentação

Criar:


INCOME

Receita

EXPENSE

Despesa

TRANSFER

Transferência

ADJUSTMENT

Ajuste


---

# 8. Categorias Financeiras

Permitir criar categorias.

Exemplos:

## Receitas


Venda produtos

Serviços

Outros ganhos


---

## Despesas


Viagem

Hospedagem

Transporte

Fornecedor

Impostos

Marketing

Taxas

Outros


---

Tabela:

## financial_categories

```sql
id

tenant_id

name

type

parent_id

status
9. Plano de Contas

Preparar estrutura hierárquica.

Exemplo:

DESPESAS

├── Viagens

│   ├── Passagens

│   ├── Hotel

│   └── Alimentação


├── Operacional

│   ├── Internet

│   └── Energia
10. Contas Financeiras

Representam onde o dinheiro está.

Exemplo:

Carteira

Banco

Conta Digital

Cartão

Caixa físico

Tabela:

financial_accounts
id

tenant_id

name

type

initial_balance

current_balance

status
11. Saldo das Contas

Fórmula:

Saldo atual

=

Saldo inicial

+

Entradas

-

Saídas
12. Contas a Receber

Controla valores futuros.

Exemplos:

Venda parcelada

Cliente devendo

Pagamento pendente

Tabela:

accounts_receivable
id

tenant_id

customer_id

sale_id

description

amount

due_date

received_date

status
13. Status Contas a Receber

Estados:

PENDING

Pendente


PAID

Recebido


OVERDUE

Atrasado


CANCELLED

Cancelado
14. Processo de Recebimento

Fluxo:

Venda parcelada

↓

Criar parcelas

↓

Aguardar vencimento

↓

Registrar pagamento

↓

Atualizar caixa
15. Contas a Pagar

Controla obrigações.

Exemplos:

Fornecedor

Hotel

Passagem

Impostos

Taxas

Tabela:

accounts_payable
id

tenant_id

supplier_id

category_id

description

amount

due_date

payment_date

status
16. Processo de Pagamento

Fluxo:

Cadastrar conta

↓

Aguardar vencimento

↓

Efetuar pagamento

↓

Baixar financeiro

↓

Atualizar saldo
17. Fluxo de Caixa

Principal ferramenta financeira.

Mostrar:

Entradas

Saídas

Saldo diário

Saldo projetado

Exemplo:

01/08

Entrada:
R$10.000


Saída:
R$3.000


Saldo:
R$7.000
18. Fluxo de Caixa Diário

Tabela:

cash_flow
id

tenant_id

date

type

description

income

expense

balance
19. Fluxo de Caixa Futuro

Permitir visualizar:

Hoje

7 dias

30 dias

90 dias

Considerar:

Contas previstas

Parcelamentos

Compras programadas
20. Registro Automático de Venda

Quando venda concluída:

Sistema gera:

Receita

Cliente

Forma pagamento

Lucro estimado
21. Registro Automático de Compra

Quando compra registrada:

Sistema gera:

Despesa

Fornecedor

Custo produto

Data pagamento
22. Controle de Despesas de Viagem

Integrar com módulo Importação.

Exemplo:

Viagem Miami


Passagem:
R$3.000


Hotel:
R$5.000


Alimentação:
R$1.000
23. Centro de Custos

Permitir classificar gastos.

Exemplo:

Importação

Operação

Marketing

Administrativo

Tabela:

cost_centers
id

tenant_id

name

description
24. Margem de Lucro

Calcular:

Lucro bruto

=

Venda

-

Custo produto
25. Lucro Líquido

Calcular:

Lucro líquido

=

Lucro bruto

-

Despesas operacionais

Exemplo:

Venda:

R$100.000


Custo produtos:

R$60.000


Despesas:

R$20.000


Lucro:

R$20.000
26. Rentabilidade por Produto

Mostrar:

Produto

Quantidade vendida

Receita

Custo

Lucro

Margem %
27. Rentabilidade por Viagem

Analisar:

Viagem

Investimento total

Produtos comprados

Venda gerada

Lucro obtido
28. Conciliação Financeira

Permitir comparar:

Sistema

versus

Banco

Exemplo:

Sistema:

PIX recebido R$500


Banco:

PIX recebido R$500


OK
29. Transferências Financeiras

Permitir:

Banco A

↓

Banco B

Não contabilizar como receita.

30. Controle de Cartões

Preparar:

Cartão utilizado

Parcelas

Taxas

Recebimentos
31. Alertas Financeiros

Criar:

Conta vencendo

Conta atrasada

Saldo baixo

Despesa acima média

Margem reduzida
32. Relatórios Financeiros

Criar:

DRE simplificado

Fluxo de caixa

Receitas

Despesas

Lucro mensal

Rentabilidade produtos

Contas vencidas

Previsão financeira
33. DRE Simplificado

Demonstrativo:

Receita Bruta

(-) Custos produtos

= Lucro Bruto


(-) Despesas

= Lucro Líquido
34. Dashboard Econômico

Indicadores:

Faturamento

Lucro

Margem média

Ticket médio

Custo estoque

Capital investido
35. Auditoria Financeira

Registrar:

Alteração lançamento

Exclusão

Pagamento realizado

Cancelamento

Alteração valor
36. Permissões
Vendedor

Pode:

Visualizar própria venda

Não pode:

Ver lucro

Ver custos

Alterar financeiro
Administrador

Pode:

Gerenciar financeiro completo

Alterar categorias

Registrar pagamentos
37. Serviços Backend

Criar:

FinancialService

CashFlowService

AccountsPayableService

AccountsReceivableService

ProfitService

ReportFinancialService
38. Controllers

Criar:

FinancialController

CashFlowController

PayableController

ReceivableController

ReportFinancialController
39. Banco Complementar
financial_transactions
id

tenant_id

account_id

category_id

type

amount

description

reference_type

reference_id

created_at
cash_flow
id

tenant_id

date

income

expense

balance
cost_centers
id

tenant_id

name

description
40. Regras de Negócio
Regra 1

Toda venda deve gerar movimentação financeira.

Regra 2

Toda compra deve gerar custo.

Regra 3

Cancelamento deve desfazer lançamentos relacionados.

Regra 4

Alterações financeiras devem possuir auditoria.

41. Critérios de Aceitação
[ ] Dashboard financeiro

[ ] Fluxo de caixa

[ ] Contas pagar

[ ] Contas receber

[ ] Categorias financeiras

[ ] Centro custos

[ ] Cálculo lucro

[ ] Rentabilidade

[ ] Integração vendas

[ ] Integração compras

[ ] Relatórios

[ ] Auditoria
Encerramento da Parte 44

O módulo Financeiro transforma o ImportControl em uma ferramenta real de gestão empresarial.

Com este módulo o proprietário terá visão completa:

Quanto investiu

Quanto vendeu

Quanto recebeu

Quanto gastou

Quanto lucrou

Onde está o dinheiro

Este módulo será fundamental para tomada de decisão e crescimento do negócio.