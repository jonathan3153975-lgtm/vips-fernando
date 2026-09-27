# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 61 — Especificação do Módulo de Importações Internacionais, Viagens, Custos em Moeda Estrangeira e Formação de Preço

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Controle completo de viagens internacionais, compras no exterior, conversão cambial, custos adicionais, formação do custo real e análise de rentabilidade

---

# 1. Objetivo

O módulo de Importações será responsável por controlar todo o processo de aquisição de mercadorias no exterior.

O objetivo é permitir que o proprietário tenha visão completa sobre:


Quanto foi gasto na viagem?

Quanto custou cada produto realmente?

Qual foi o impacto do dólar?

Quais despesas aumentaram o custo?

Qual margem de lucro será obtida?

Qual importação trouxe maior retorno?


---

# 2. Conceito Geral

O fluxo de importação será:


Planejamento viagem

    ↓

Cadastro despesas previstas

    ↓

Viagem realizada

    ↓

Compra produtos

    ↓

Registro valores moeda estrangeira

    ↓

Conversão cambial

    ↓

Rateio dos custos

    ↓

Formação custo real

    ↓

Entrada estoque

    ↓

Venda

    ↓

Análise lucro


---

# 3. Requisitos Funcionais

O módulo deverá permitir:


RF001 - Criar importações

RF002 - Registrar viagens

RF003 - Controlar moedas

RF004 - Registrar despesas internacionais

RF005 - Registrar compras

RF006 - Calcular custo real

RF007 - Integrar estoque

RF008 - Integrar financeiro

RF009 - Calcular ROI

RF010 - Gerar relatórios


---

# 4. Conceito de Importação

Uma importação representa uma operação completa.

Exemplo:


Importação:

Viagem Estados Unidos Janeiro/2026

Inclui:

Passagens

Hotel

Alimentação

Compras

Transporte

Taxas


---

# 5. Cadastro da Importação

Dados principais:


Nome

Descrição

País destino

Cidade

Data início

Data retorno

Moeda utilizada

Cotação utilizada

Responsável

Status


---

# 6. Tabela imports

```sql
CREATE TABLE imports (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

description TEXT,

country VARCHAR(100),

city VARCHAR(100),

start_date DATE,

end_date DATE,

currency VARCHAR(10),

exchange_rate DECIMAL(10,4),

status VARCHAR(30),

created_at TIMESTAMP,

updated_at TIMESTAMP

);
7. Status da Importação

Estados:

PLANNED

Planejada


IN_PROGRESS

Em andamento


COMPLETED

Concluída


CANCELLED

Cancelada
8. Controle de Moedas

O sistema deverá aceitar:

Real

Dólar americano

Euro

Peso

Guarani

Outras moedas
9. Cadastro de Cotação

Registrar:

Moeda

Data

Valor cotação

Fonte

Usuário responsável
10. Tabela Exchange Rates
CREATE TABLE exchange_rates (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

currency VARCHAR(10),

rate DECIMAL(10,4),

reference_date DATE,

created_at TIMESTAMP

);
11. Regra Cambial

Todo valor estrangeiro deverá possuir:

Valor original

Moeda

Cotação aplicada

Valor convertido

Exemplo:

Produto:

US$500


Cotação:

5,20


Custo:

R$2.600
12. Despesas da Viagem

Cadastrar:

Passagens

Hospedagem

Alimentação

Transporte

Seguro viagem

Taxas

Bagagem

Outros
13. Tabela Import Expenses
CREATE TABLE import_expenses (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

import_id BIGINT,

category VARCHAR(100),

description VARCHAR(255),

currency VARCHAR(10),

amount DECIMAL(12,2),

exchange_rate DECIMAL(10,4),

converted_amount DECIMAL(12,2),

expense_date DATE,

created_at TIMESTAMP

);
14. Exemplo de Despesas

Viagem:

Passagem:

US$700


Hotel:

US$500


Transporte:

US$200

Conversão:

Total:

US$1.400


Cotação 5,20


R$7.280
15. Classificação de Custos

Separar:

Custos diretos

Ligados ao produto:

Compra mercadoria

Frete

Taxas importação
Custos indiretos

Ligados à operação:

Hotel

Passagem

Alimentação

Transporte
16. Compras da Importação

Registrar produtos adquiridos.

Dados:

Produto

Quantidade

Preço unidade

Moeda

Cotação

Valor convertido
17. Tabela Import Items
CREATE TABLE import_items (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

import_id BIGINT,

product_id BIGINT,

quantity DECIMAL(12,3),

currency VARCHAR(10),

unit_price DECIMAL(12,2),

exchange_rate DECIMAL(10,4),

converted_unit_price DECIMAL(12,2),

created_at TIMESTAMP

);
18. Produto Novo ou Existente

Ao registrar compra:

Sistema deverá permitir:

Produto já cadastrado

OU

Criar novo produto
19. Cálculo do Custo Real

O sistema deverá calcular:

Custo produto

+

Despesas rateadas

+

Custos adicionais

=

Custo real
20. Rateio de Custos

Métodos disponíveis:

Por quantidade

Por valor

Por peso

Manual
21. Exemplo de Rateio

Compra:

Produto A:

US$1.000


Produto B:

US$1.000

Despesas:

R$2.000

Rateio:

Produto A:

+R$1.000


Produto B:

+R$1.000
22. Tabela Cost Allocation
CREATE TABLE cost_allocations (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

import_id BIGINT,

product_id BIGINT,

allocation_type VARCHAR(30),

allocated_amount DECIMAL(12,2),

created_at TIMESTAMP

);
23. Custo Médio do Produto

Atualizar:

Custo anterior

+

Nova aquisição

=

Novo custo médio
24. Formação de Preço

O sistema deverá sugerir preço:

Baseado em:

Custo real

Margem desejada

Concorrência

Categoria
25. Fórmula Preço Venda

Exemplo:

Custo:

R$1.000


Margem:

40%


Preço sugerido:

R$1.400
26. Margem Configurável

Permitir:

Margem padrão empresa

Margem por categoria

Margem por produto
27. Tabela Pricing Rules
CREATE TABLE pricing_rules (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

category_id BIGINT,

profit_margin DECIMAL(5,2),

created_at TIMESTAMP

);
28. Entrada no Estoque

Ao concluir importação:

Produtos adicionados

↓

Estoque atualizado

↓

Movimentação criada

↓

Financeiro atualizado
29. Integração Financeira

Criar:

Despesa importação

Pagamento realizado

Investimento realizado
30. Análise Financeira da Importação

Calcular:

Investimento total

Custo produtos

Despesas

Receita esperada

Lucro esperado

ROI
31. ROI da Importação

Fórmula:

ROI

=

(Lucro / Investimento)

× 100

Exemplo:

Investimento:

R$50.000


Lucro:

R$20.000


ROI:

40%
32. Dashboard da Importação

Mostrar:

Valor investido

Quantidade produtos

Custo médio

Lucro esperado

ROI

Status
33. Relatório Comparativo

Comparar:

Importação Janeiro

X

Importação Fevereiro

Critérios:

Investimento

Margem

Lucro

ROI
34. Alertas

Criar:

Cotação aumentou

Produto ficou caro

Margem abaixo esperado

Importação sem retorno
35. Serviços Backend

Criar:

ImportService

CurrencyService

ImportCostService

PricingService

ROIService

ImportReportService
36. Controllers

Criar:

ImportController

CurrencyController

ImportExpenseController

ImportReportController
37. API Importações

Endpoints:

GET /api/v1/imports

POST /api/v1/imports

PUT /api/v1/imports/{id}

POST /api/v1/imports/{id}/expenses

POST /api/v1/imports/{id}/items

POST /api/v1/imports/{id}/complete
38. Permissões

Criar:

imports.view

imports.create

imports.edit

imports.complete

imports.delete

imports.reports
39. Auditoria

Registrar:

Alteração cotação

Alteração custos

Inclusão produto

Conclusão importação

Cancelamento
40. Regras de Negócio
Regra 1

Nenhum produto importado entra no estoque sem custo definido.

Regra 2

Toda despesa internacional deve possuir moeda.

Regra 3

Toda conversão deve guardar a cotação utilizada.

Regra 4

Importações concluídas não podem ser excluídas.

Regra 5

Custos alterados devem gerar histórico.

41. Critérios de Aceitação
[ ] Cadastro importação funcionando

[ ] Controle moeda funcionando

[ ] Conversão cambial funcionando

[ ] Despesas registradas

[ ] Produtos importados cadastrados

[ ] Custo real calculado

[ ] Rateio funcionando

[ ] Estoque integrado

[ ] Financeiro integrado

[ ] ROI calculado

[ ] Relatórios funcionando
Encerramento da Parte 61

O módulo de Importações será um dos diferenciais do ImportControl.

Ele permitirá que o proprietário saiba exatamente:

Quanto custou trazer o produto?

Qual foi o impacto do dólar?

Qual produto vale a pena importar?

Qual viagem trouxe maior lucro?

Quanto posso cobrar para manter minha margem?

Este módulo transforma uma simples compra internacional em uma operação profissional de gestão de custos e rentabilidade.