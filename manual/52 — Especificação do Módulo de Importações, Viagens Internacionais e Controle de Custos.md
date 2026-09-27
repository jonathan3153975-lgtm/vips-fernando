# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 52 — Especificação do Módulo de Importações, Viagens Internacionais e Controle de Custos

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Gestão de viagens internacionais, compras no exterior, despesas, conversão cambial e composição do custo final dos produtos

---

# 1. Objetivo

O módulo de Importações será responsável por controlar todo o processo de aquisição de mercadorias no exterior.

O objetivo é permitir que o proprietário tenha uma visão completa do custo real de cada operação internacional.

O módulo deverá controlar:


Viagens internacionais

Passagens

Hospedagem

Alimentação

Transporte

Taxas

Compras realizadas

Moedas diferentes

Conversão cambial

Rateio de despesas

Custo final dos produtos


---

# 2. Conceito Geral

O processo de importação será representado pelo fluxo:


Planejamento da viagem

    ↓

Cadastro da viagem

    ↓

Registro das despesas

    ↓

Registro das compras

    ↓

Associação dos produtos

    ↓

Rateio dos custos

    ↓

Entrada no estoque

    ↓

Análise de rentabilidade


---

# 3. Objetivos Comerciais

O módulo deverá responder:


Quanto custou essa viagem?

Quanto investi em produtos?

Quanto gastei em despesas?

Qual produto teve maior custo?

Qual foi o lucro dessa operação?

Qual viagem foi mais rentável?


---

# 4. Requisitos Funcionais

O módulo deverá permitir:


RF001 - Criar viagens

RF002 - Registrar destinos

RF003 - Controlar datas

RF004 - Registrar despesas

RF005 - Registrar moedas diferentes

RF006 - Converter valores

RF007 - Associar produtos

RF008 - Calcular custo final

RF009 - Gerar relatórios

RF010 - Integrar com estoque


---

# 5. Cadastro da Viagem

Cada viagem deverá possuir:


Código

Descrição

País destino

Cidade

Data saída

Data retorno

Responsável

Status

Observações


---

# 6. Tela Cadastro Viagem

Layout:


Nova Importação

Descrição:

[________________]

País:

[________________]

Cidade:

[________________]

Data saída:

[//____]

Data retorno:

[//____]

[Salvar]


---

# 7. Código da Importação

Gerar automaticamente:

Exemplo:


IMP-2026-0001

IMP-2026-0002


Formato:


IMP

Ano

Sequencial


---

# 8. Status da Importação

Estados:


PLANNED

Planejada

TRAVELING

Em viagem

PURCHASING

Realizando compras

RETURNED

Retornada

FINISHED

Finalizada

CANCELLED

Cancelada


---

# 9. Dados da Viagem

Informações:


Destino

Motivo

Observações

Quantidade dias

Responsável


---

# 10. Participantes da Viagem

Preparar estrutura para:


Viajante principal

Acompanhantes

Funcionários

Parceiros


Tabela:


import_travelers


---

# 11. Controle de Datas

Sistema deverá calcular:


Quantidade de dias

Tempo viagem

Tempo até retorno

Histórico


---

Exemplo:


Saída:

01/08/2026

Retorno:

10/08/2026

Duração:

10 dias


---

# 12. Despesas da Viagem

O sistema deverá controlar todas as despesas.

Categorias:


Passagens

Hospedagem

Alimentação

Transporte

Seguro

Taxas

Bagagem

Outros


---

# 13. Tabela Travel Expenses

Estrutura:

```sql
CREATE TABLE travel_expenses (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

import_id BIGINT,

category_id BIGINT,

description VARCHAR(255),

currency_id BIGINT,

amount DECIMAL(12,2),

exchange_rate DECIMAL(12,4),

converted_amount DECIMAL(12,2),

expense_date DATE,

attachment VARCHAR(255),

created_at TIMESTAMP

);
14. Categorias de Despesas

Criar tabela:

expense_categories

Exemplo:

Passagem aérea

Hotel

Restaurante

Uber

Táxi

Impostos

Taxas
15. Controle de Moeda

Toda despesa deverá informar:

Moeda

Valor original

Cotação utilizada

Valor convertido

Exemplo:

Compra:

Hotel:

US$ 500


Cotação:

5,20


Total:

R$ 2.600
16. Conversão Cambial

Fórmula:

Valor Real

=

Valor Estrangeiro

×

Cotação

Exemplo:

US$ 100

×

5,30

=

R$530
17. Cotação Histórica

Salvar:

Valor moeda

Data

Origem

Usuário

Objetivo:

Manter precisão financeira

Permitir auditoria

Recalcular custos
18. Upload de Comprovantes

Permitir anexar:

Notas fiscais

Recibos

Bilhetes

Comprovantes

Fotos

Formatos:

PDF

JPG

PNG
19. Despesas de Passagem

Campos:

Companhia aérea

Origem

Destino

Data voo

Valor

Moeda

Passageiro
20. Despesas de Hospedagem

Campos:

Hotel

Check-in

Check-out

Quantidade noites

Valor diária

Total
21. Despesas Alimentação

Campos:

Local

Data

Quantidade pessoas

Valor

Observação
22. Transporte

Controlar:

Uber

Táxi

Aluguel carro

Combustível

Estacionamento
23. Compras Durante a Viagem

O sistema deverá permitir registrar compras realizadas:

Produto

Quantidade

Fornecedor

Valor moeda estrangeira

Cotação

Custo convertido
24. Tabela Import Items
CREATE TABLE import_items (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

import_id BIGINT,

product_id BIGINT,

quantity INT,

currency_id BIGINT,

unit_value DECIMAL(12,2),

exchange_rate DECIMAL(12,4),

converted_value DECIMAL(12,2),

total_value DECIMAL(12,2)

);
25. Fornecedor Internacional

Cadastrar:

Nome

País

Cidade

Contato

Site

Observações

Tabela:

international_suppliers
26. Documento de Compra

Permitir registrar:

Nota fiscal

Invoice

Recibo

Documento importação
27. Controle de Bagagem

Preparar controle:

Peso permitido

Peso utilizado

Quantidade volumes

Taxa excesso
28. Taxas e Impostos

Permitir registrar:

Impostos

Taxas alfandegárias

Despachante

Outros custos
29. Custo Total da Viagem

Fórmula:

Custo viagem

=

Passagens

+

Hospedagem

+

Alimentação

+

Transporte

+

Taxas
30. Custo Total da Importação

Fórmula:

Investimento total

=

Produtos comprados

+

Custos viagem

+

Custos adicionais
31. Rateio de Despesas

Permitir distribuir custos entre produtos.

Métodos:

Proporcional ao valor

Proporcional quantidade

Manual
32. Exemplo Rateio

Viagem:

Despesas:

R$10.000

Produtos:

Produto A

70%


Produto B

30%

Resultado:

Produto A:

R$7.000


Produto B:

R$3.000
33. Tela Resumo Importação

Exibir:

--------------------------------

Importação IMP-2026-0001


Investimento:

R$150.000


Produtos:

R$120.000


Despesas:

R$30.000


Produtos:

250 unidades


--------------------------------
34. Indicadores da Importação

Mostrar:

Total investido

Quantidade produtos

Custo médio

Despesas

Lucro esperado

ROI previsto
35. Rentabilidade da Importação

Calcular:

ROI

=

Lucro esperado

/

Investimento total

Exemplo:

Investimento:

R$100.000


Venda prevista:

R$160.000


ROI:

60%
36. Relatório por Viagem

Gerar:

Resumo financeiro

Produtos comprados

Despesas

Custos

Lucro
37. Comparativo de Viagens

Permitir:

Viagem Janeiro

x

Viagem Junho

Comparar:

Investimento

Quantidade produtos

Lucro

ROI
38. Integração com Produtos

Ao finalizar importação:

Produtos atualizados

Custos recalculados

Estoque abastecido

Histórico criado
39. Integração com Financeiro

Criar lançamentos:

Saída financeira

Categoria:

Importação

Exemplo:

Compra produtos

-R$100.000


Viagem

-R$15.000
40. Integração com Estoque

Fluxo:

Importação finalizada

↓

Entrada estoque

↓

Atualização quantidade

↓

Atualização custo médio
41. Serviços Backend

Criar:

ImportService

TravelExpenseService

CurrencyConversionService

CostAllocationService

ImportReportService
42. Controllers

Criar:

ImportController

TravelExpenseController

ImportReportController
43. API

Endpoints:

GET    /api/v1/imports

POST   /api/v1/imports

PUT    /api/v1/imports/{id}

DELETE /api/v1/imports/{id}


POST /api/v1/imports/{id}/expenses

POST /api/v1/imports/{id}/finish
44. Regras de Negócio
Regra 1

Importação finalizada não pode ser alterada sem permissão.

Regra 2

Toda despesa deve possuir moeda.

Regra 3

Todo custo estrangeiro deve possuir cotação.

Regra 4

Finalização gera atualização de estoque.

Regra 5

Alterações geram auditoria.

45. Critérios de Aceitação
[ ] Cadastro de viagens funcionando

[ ] Controle de datas

[ ] Despesas cadastradas

[ ] Moedas diferentes

[ ] Conversão cambial

[ ] Compras vinculadas

[ ] Rateio funcionando

[ ] Custo real calculado

[ ] Integração estoque

[ ] Integração financeiro

[ ] Relatórios funcionando

[ ] Auditoria aplicada
Encerramento da Parte 52

O módulo de Importações será um dos diferenciais do ImportControl.

Ele permitirá que o proprietário deixe de enxergar apenas o preço pago no exterior e passe a conhecer o verdadeiro custo da operação.

A plataforma conseguirá responder:

Quanto investi?

Quanto custou cada produto?

Qual viagem foi melhor?

Qual produto trouxe maior retorno?

Quanto preciso vender para recuperar o investimento?