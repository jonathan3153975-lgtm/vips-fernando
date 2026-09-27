# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 32 — Especificação do Módulo de Compras, Importação e Controle de Viagens

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Gestão de viagens internacionais, compras no exterior, custos de importação e integração operacional

---

# 1. Objetivo

O módulo de Compras e Viagens será responsável por controlar todo o processo de aquisição de mercadorias fora do país.

Este módulo representa uma das principais diferenças do ImportControl em relação a sistemas comerciais tradicionais.

O sistema deverá compreender o fluxo:


Planejamento da viagem

↓

Viagem internacional

↓

Despesas da viagem

↓

Compra das mercadorias

↓

Conversão cambial

↓

Cálculo do custo real

↓

Entrada no estoque

↓

Venda


---

# 2. Conceito da Operação

O negócio funciona baseado em ciclos de importação:

Exemplo:


Janeiro:

Viagem para Miami

Investimento:

USD 10.000

Compra:

Eletrônicos

Retorno ao Brasil

Venda:

R$80.000


O sistema deve permitir saber:


Quanto foi gasto na viagem

Quanto custaram os produtos

Quanto foi investido

Quanto retornou em vendas

Qual foi o lucro real


---

# 3. Objetivos do Módulo

Permitir:


Cadastrar viagens

Controlar despesas

Registrar compras internacionais

Controlar moedas

Calcular custo real

Relacionar produtos comprados

Gerar indicadores de rentabilidade


---

# 4. Menu Compras e Viagens

Estrutura:


Compras

├── Viagens

├── Nova viagem

├── Despesas

├── Compras internacionais

├── Fornecedores

├── Histórico

└── Relatórios


---

# 5. Conceito de Viagem

Uma viagem representa um ciclo de aquisição.

Exemplo:


Viagem:

Miami Fevereiro 2026

Objetivo:

Compra de eletrônicos

Status:

Finalizada


---

# 6. Cadastro de Viagem

Campos:


Nome da viagem

Destino

País

Cidade

Data saída

Data retorno

Responsável

Moeda principal

Cotação utilizada

Observações


---

# 7. Status da Viagem

Estados:


PLANNING

Em planejamento

ACTIVE

Em andamento

COMPLETED

Finalizada

CANCELLED

Cancelada


---

# 8. Tela Lista de Viagens

Exibição:


Viagens

[ Nova viagem ]

Nome

Destino

Data

Investimento

Compras

Resultado

Status

Ações


---

# 9. Dashboard da Viagem

Mostrar:


Destino

Período

Valor investido

Total compras

Total despesas

Quantidade produtos

Lucro estimado


---

# 10. Conversão Cambial

Cada viagem poderá possuir sua própria cotação.

Exemplo:


Viagem:

Miami

Cotação utilizada:

USD = R$5,15


---

Motivo:

A cotação muda diariamente.

O sistema deve preservar:


Cotação utilizada no momento da compra


---

# 11. Histórico Cambial da Viagem

Registrar:


Data

Moeda

Cotação

Usuário

Observação


---

Tabela:

## trip_exchange_rates

```sql
id

trip_id

currency_id

rate

date

user_id

created_at
12. Despesas da Viagem

Controlar todos os gastos:

Exemplos:

Passagem aérea

Hotel

Alimentação

Transporte

Seguro viagem

Bagagem

Taxas aeroportuárias

Outros
13. Cadastro de Despesa

Campos:

Categoria

Descrição

Data

Moeda

Valor original

Cotação

Valor convertido

Comprovante

Observações

Exemplo:

Hotel


USD:

500


Cotação:

5,20


Valor:

R$2.600
14. Categorias de Despesas

Tabela:

expense_categories

Exemplo:

Transporte

Hospedagem

Alimentação

Taxas

Compras

Outros
15. Anexos de Despesas

Permitir anexar:

Nota fiscal

Recibo

Foto

Comprovante

Formatos:

PDF

JPG

PNG
16. Compras Internacionais

Uma compra representa aquisição de mercadorias.

Exemplo:

Compra:

Apple Store Miami


Valor:

USD 5.000
17. Cadastro de Compra

Campos:

Viagem relacionada

Fornecedor

Data compra

País

Moeda

Valor total

Cotação

Valor convertido

Observações
18. Itens da Compra

Cada compra possui produtos:

Exemplo:

Compra:

Apple Store


Produtos:

10 iPhones

5 iPads

Campos:

Produto

Quantidade

Valor unitário moeda origem

Valor total moeda origem

Valor convertido
19. Cálculo do Custo Real

O sistema deverá distribuir custos.

Exemplo:

Compra:

Produtos:

R$20.000

Despesas:

Passagem:

R$3.000


Hotel:

R$2.000


Taxas:

R$1.000

Custo total:

R$26.000
20. Rateio de Custos

Permitir escolher:

Rateio proporcional ao valor

Exemplo:

Produto A:

60% da compra

Recebe:

60% das despesas
Rateio por quantidade

Exemplo:

Cada item recebe igual parcela
21. Formação do Custo do Produto

Fórmula:

Custo produto

+

Frete

+

Impostos

+

Rateio viagem

=

Custo real
22. Integração com Produtos

Ao finalizar compra:

Sistema pergunta:

Deseja atualizar custos dos produtos?

Opções:

Sim, atualizar automaticamente

Não, revisar manualmente
23. Entrada Automática no Estoque

Fluxo:

Compra finalizada

↓

Produtos recebidos

↓

Entrada estoque

↓

Histórico criado
24. Controle de Compra Parcial

Permitir:

Compra registrada

Alguns produtos recebidos

Outros pendentes

Status:

PENDING

PARTIAL

COMPLETED
25. Fornecedores Internacionais

Campos:

Nome

País

Cidade

Site

Contato

Telefone

Email

Observações

Exemplos:

Amazon USA

Best Buy

Apple Store

Fornecedor local
26. Histórico de Compras

Mostrar:

Produto

Data

Fornecedor

Valor

Cotação

Viagem
27. Relatórios de Viagem

Criar:

Relatório financeiro da viagem

Mostrar:

Total investido

Despesas

Compras

Custo médio
Relatório de rentabilidade

Mostrar:

Produtos comprados

Valor venda esperado

Lucro estimado
28. Indicadores da Viagem

Criar:

ROI da viagem

Margem prevista

Custo total

Retorno esperado

Fórmula:

ROI

=

(Lucro esperado / Investimento) × 100
29. Tela de Despesas

Layout:

Despesas da viagem


[ Nova despesa ]


Categoria

Descrição

Moeda

Valor

Convertido

Data

Ações
30. Tela de Compra Internacional

Layout:

Compra


Fornecedor


Produtos


Valor original


Cotação


Valor real


[Finalizar compra]
31. Banco de Dados
trips
id

tenant_id

name

country

city

start_date

end_date

currency_id

exchange_rate

status

notes

created_at

updated_at
trip_expenses
id

tenant_id

trip_id

category_id

description

currency_id

amount

exchange_rate

converted_amount

expense_date

attachment

created_at
purchases
id

tenant_id

trip_id

supplier_id

currency_id

total_amount

exchange_rate

converted_amount

status

created_at
purchase_items
id

purchase_id

product_id

quantity

unit_price

total

converted_total
trip_cost_allocations

Nova tabela:

id

trip_id

product_id

amount

allocation_type

created_at
32. Services Backend

Criar:

TripService

ExpenseService

PurchaseService

ImportCostService

ExchangeRateService

CostAllocationService
33. Controllers

Criar:

TripController

TripExpenseController

PurchaseController

SupplierController
34. Regras de Segurança

Controlar:

Quem pode criar viagens

Quem pode registrar compras

Quem pode alterar custos

Quem pode finalizar importação
35. Auditoria

Registrar:

Criação viagem

Alteração cotação

Inclusão compra

Alteração custo

Finalização
36. Automações Futuras

Preparar:

Consulta automática dólar

Integração cartão internacional

OCR de notas fiscais

Importação automática de pedidos
37. Critérios de Aceitação
[ ] Criar viagem

[ ] Registrar despesas

[ ] Registrar compras

[ ] Controlar moedas

[ ] Converter valores

[ ] Calcular custo real

[ ] Atualizar produtos

[ ] Gerar entrada estoque

[ ] Gerar relatórios

[ ] Histórico completo
Encerramento da Parte 32

O módulo de Compras e Viagens transforma o ImportControl em um sistema especializado em negócios de importação.

A grande vantagem competitiva será conseguir responder:

Quanto realmente custou minha mercadoria?

Quanto investi nesta viagem?

Qual foi meu lucro real?

Qual produto vale mais a pena trazer novamente?

Esse módulo será um dos principais diferenciais do sistema SaaS.