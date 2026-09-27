# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 41 — Especificação do Módulo de Compras Internacionais, Importação e Formação de Custo

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Controle de compras no exterior, custos de importação, conversão cambial e formação de preço

---

# 1. Objetivo

O módulo de Compras Internacionais será responsável por controlar todo o processo de aquisição de mercadorias realizadas pelo proprietário no exterior.

O módulo deverá permitir registrar:


Viagem realizada

Local da compra

Fornecedor internacional

Produtos adquiridos

Valor em moeda estrangeira

Cotação utilizada

Custos adicionais

Custo real do produto

Margem de lucro

Preço sugerido de venda


---

# 2. Conceito do Processo de Importação

Fluxo principal:


Planejamento da viagem

↓

Registro das despesas

↓

Compra dos produtos

↓

Conversão cambial

↓

Cálculo do custo real

↓

Entrada no estoque

↓

Definição preço venda

↓

Comercialização


---

# 3. Objetivos do Módulo

Permitir ao proprietário responder:


Quanto realmente paguei neste produto?

Quanto custou minha viagem?

Qual foi o custo total da importação?

Qual minha margem real?

Qual produto trouxe maior retorno?


---

# 4. Estrutura Geral

Menu:


Importações

├── Viagens

├── Compras internacionais

├── Produtos adquiridos

├── Custos adicionais

├── Cálculo de custo

├── Formação de preço

└── Histórico


---

# 5. Cadastro de Viagens

A viagem será a entidade principal da importação.

Exemplo:


Viagem EUA Janeiro/2026

Destino:
Miami

Período:
10/01/2026 até 20/01/2026


---

# 6. Dados da Viagem

Campos:


Nome da viagem

Destino

País

Data início

Data fim

Responsável

Moeda principal

Cotação utilizada

Observações


---

Tabela:

## trips

```sql
id

tenant_id

name

destination

country

start_date

end_date

currency_id

exchange_rate

notes

status

created_at
7. Status da Viagem

Estados:

PLANNED

Planejada


IN_PROGRESS

Em andamento


FINISHED

Finalizada


CANCELLED

Cancelada
8. Despesas da Viagem

Registrar todos os custos relacionados.

Exemplos:

Passagem aérea

Hotel

Alimentação

Transporte

Seguro viagem

Bagagem

Taxas

Outros

Tabela:

trip_expenses
id

trip_id

category_id

description

currency_id

amount

exchange_rate

converted_value

date

created_at
9. Categorias de Despesas

Criar:

Passagem

Hospedagem

Alimentação

Transporte

Documentação

Taxas

Outros
10. Conversão Monetária

O sistema deverá trabalhar com múltiplas moedas.

Exemplo:

Compra:

USD 1.000

Cotação:

1 USD = R$5,20

Sistema calcula:

Custo convertido:

R$5.200
11. Histórico Cambial

Nunca sobrescrever cotação antiga.

Guardar:

Data

Moeda

Cotação utilizada

Fonte

Tabela:

exchange_rates
id

currency_id

rate

date

source

created_at
12. Cadastro de Fornecedores Internacionais

Permitir:

Lojas

Distribuidores

Fabricantes

Representantes

Dados:

Nome

País

Cidade

Contato

Site

Observações
13. Registro de Compra Internacional

Uma viagem poderá possuir várias compras.

Exemplo:

Viagem Miami 2026

Compra 01:
Apple Store

Compra 02:
Best Buy

Compra 03:
Fornecedor X

Tabela:

purchases
id

tenant_id

trip_id

supplier_id

purchase_date

currency_id

exchange_rate

total_foreign

total_converted

status

created_at
14. Itens da Compra

Cada compra terá produtos.

Exemplo:

Compra Apple Store

iPhone 15 Pro
10 unidades

AirPods
20 unidades

Tabela:

purchase_items
id

purchase_id

product_id

quantity

unit_cost_foreign

unit_cost_converted

total_cost

created_at
15. Cadastro de Produto Durante Compra

Possibilitar:

Produto já cadastrado

ou

Novo produto

Novo produto deverá solicitar:

Nome

Categoria

Marca

Modelo

Fornecedor

Custo compra

Moeda
16. Cálculo do Custo Real

O custo real não será apenas o valor pago.

Fórmula:

Custo Real Produto

=

Valor compra convertido

+

Rateio despesas viagem

+

Custos adicionais
17. Rateio de Custos

O sistema deverá permitir diferentes métodos.

Método 1 — Rateio por valor

Exemplo:

Compra total:

R$50.000

Despesa viagem:

R$5.000

Percentual:

10%

Cada produto recebe:

Custo + 10%
Método 2 — Rateio por quantidade

Exemplo:

100 produtos

Despesa:

R$5.000

Cada unidade:

R$50
Método 3 — Manual

Usuário define:

Produto A:
R$100

Produto B:
R$200
18. Custos Adicionais

Registrar:

Frete

Seguro

Taxas

Impostos

Bagagem extra

Conversão cambial

Outros

Tabela:

import_costs
id

purchase_id

description

amount

currency_id

converted_value

created_at
19. Formação do Custo Unitário

Exemplo:

Produto:

iPhone

Compra:

USD 800

Conversão:

R$4.160

Rateio:

R$200

Custo final:

R$4.360
20. Histórico de Custos

O sistema deverá manter histórico.

Exemplo:

Produto

Janeiro:
R$4.000


Março:
R$4.300


Junho:
R$4.500

Tabela:

product_cost_history
id

product_id

purchase_id

cost

currency

date
21. Formação do Preço de Venda

O sistema deverá permitir:

Preço manual

Margem percentual

Markup

Sugestão automática
22. Método Margem

Exemplo:

Custo:

R$1.000

Margem:

40%

Preço:

R$1.400
23. Método Markup

Fórmula:

Preço Venda

=

Custo × Markup

Exemplo:

Custo:
1000

Markup:
1.8


Venda:
1800
24. Preço Diferenciado

Permitir:

Preço padrão

Preço promoção

Preço cliente especial

Preço mínimo permitido
25. Controle de Alteração de Preço

Toda alteração deve registrar:

Produto

Preço anterior

Novo preço

Usuário

Data

Motivo
26. Entrada Automática no Estoque

Após finalizar compra:

Fluxo:

Compra aprovada

↓

Produtos adicionados

↓

Estoque atualizado

↓

Custo atualizado

↓

Histórico criado
27. Status da Compra

Estados:

DRAFT

Rascunho


CONFIRMED

Confirmada


RECEIVED

Recebida


CANCELLED

Cancelada
28. Relatórios do Módulo

Criar:

Compras por viagem

Produtos importados

Custo médio

Variação cambial

Rentabilidade importação

Despesas viagem
29. Dashboard da Importação

Indicadores:

Total investido

Quantidade produtos

Custo médio

Maior compra

Maior fornecedor

ROI estimado
30. Alertas Inteligentes

Exemplos:

Produto comprado aumentou custo 20%

Cotação atual desfavorável

Margem abaixo configurada

Produto sem venda após importação
31. Serviços Backend

Criar:

TripService

PurchaseService

ImportCostService

ExchangeRateService

CostCalculationService

PriceFormationService
32. Controllers

Criar:

TripController

PurchaseController

ImportCostController

ExchangeController

CostController
33. Regras de Negócio
Regra 1

Produto comprado sempre deve possuir:

Moeda

Valor origem

Cotação

Valor convertido
Regra 2

Nenhuma compra pode entrar no estoque sem custo calculado.

Regra 3

Alterações de custo devem gerar histórico.

Regra 4

Todas as despesas devem estar vinculadas:

À viagem

ou

À compra específica
34. Banco de Dados Complementar
import_costs
id

purchase_id

description

amount

currency_id

converted_value
product_cost_history
id

product_id

purchase_id

cost

created_at
35. Critérios de Aceitação
[ ] Cadastro de viagens

[ ] Registro despesas

[ ] Controle moedas

[ ] Cotação histórica

[ ] Cadastro fornecedores

[ ] Compra internacional

[ ] Conversão automática

[ ] Rateio despesas

[ ] Custo real calculado

[ ] Formação preço venda

[ ] Entrada estoque

[ ] Histórico custos

[ ] Relatórios funcionando
Encerramento da Parte 41

O módulo de Compras Internacionais será um dos principais diferenciais do ImportControl.

Ele permitirá que o proprietário tenha visão real do negócio, considerando:

Valor pago no exterior

Câmbio

Despesas da viagem

Custo final

Margem real

Lucro verdadeiro

Com este módulo, o sistema deixa de ser apenas um controle de vendas e passa a ser uma ferramenta completa de gestão de importação.