# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 11 — Módulo de Viagens e Gestão de Importações

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação funcional e técnica do módulo de viagens, despesas e importações

---

# 1. Objetivo

O módulo de Viagens será responsável por controlar todo o processo de importação realizado pelo proprietário do negócio.

O objetivo é permitir que o usuário consiga:

- cadastrar viagens internacionais;
- controlar despesas realizadas durante a viagem;
- registrar gastos em diferentes moedas;
- converter valores automaticamente para reais;
- anexar comprovantes;
- calcular custo real da importação;
- vincular produtos comprados à viagem;
- distribuir custos indiretos nos produtos;
- gerar histórico completo da operação.

---

# 2. Conceito do Processo

Fluxo real do negócio:
Planejamento da viagem

↓

Compra de passagens

↓

Hospedagem

↓

Alimentação

↓

Viagem internacional

↓

Compra dos produtos

↓

Retorno ao Brasil

↓

Cadastro dos produtos

↓

Rateio dos custos

↓

Venda


---

# 3. Estrutura do Módulo

O módulo será dividido em:


Viagens

├── Cadastro

├── Planejamento

├── Despesas

├── Produtos Comprados

├── Documentos

├── Rateio de Custos

├── Conversão Monetária

└── Fechamento


---

# 4. Status da Viagem

Uma viagem terá estados:

```text
PLANNING

OPEN

BUYING

RETURNED

PROCESSING

CLOSED

CANCELED
PLANNING

Viagem planejada.

Permite:

cadastrar destino;
definir datas;
estimar custos.
OPEN

Viagem iniciada.

Permite:

registrar despesas;
cadastrar compras.
BUYING

Momento de aquisição dos produtos.

RETURNED

Usuário retornou.

Permite:

finalizar registros;
iniciar fechamento.
PROCESSING

Produtos e custos sendo organizados.

CLOSED

Viagem encerrada.

Não permite alterações sem permissão especial.

5. Tela Principal de Viagens

Listagem:

------------------------------------------------

Viagens

[ Nova Viagem ]

------------------------------------------------

Miami 2026

Status: Em andamento

01/08/2026 - 15/08/2026

Custo:
US$ 3.500

R$ 18.900

[Acessar]

------------------------------------------------
6. Cadastro de Viagem

Campos:

Nome da viagem

País

Cidade

Data saída

Data retorno

Moeda principal

Cotação inicial

Observações

Exemplo:

Miami Janeiro 2026

Estados Unidos

Miami

10/01/2026

20/01/2026

USD

5,40
7. Cotação Monetária

Cada viagem deverá possuir sua própria cotação histórica.

Motivo:

O custo de um produto deve permanecer igual ao momento da compra.

Exemplo:

Compra:

10/01/2026

USD = R$5,40

Produto:

US$100

Custo real:

R$540

Mesmo que depois:

USD = R$6,00

O custo não muda.

8. Cadastro de Moedas

O sistema deverá suportar:

Inicialmente:

BRL

USD

EUR

ARS

PYG

Futura integração:

API de câmbio.

9. Conversão Monetária

Todo lançamento financeiro deverá possuir:

Valor original

Moeda

Cotação utilizada

Valor convertido

Exemplo:

Despesa:

Hotel

USD 300

Cotação 5,40

Total:

R$1.620
10. Despesas da Viagem

Permitir cadastrar:

Transporte

Exemplos:

passagem aérea;
combustível;
aluguel de veículo;
transporte local.
Hospedagem

Exemplos:

hotel;
apartamento;
diária.
Alimentação

Exemplos:

restaurantes;
mercado.
Operacionais

Exemplos:

taxas;
seguros;
documentos.
Outros

Categoria livre.

11. Cadastro de Despesa

Campos:

Categoria

Descrição

Data

Moeda

Valor

Cotação

Valor convertido

Comprovante

Observação

Exemplo:

Hotel

Miami Beach

USD

800

5,40

R$4.320
12. Comprovantes

Permitir anexar:

PDF

JPG

PNG

Exemplos:

notas fiscais;
recibos;
comprovantes.

Local:

storage/uploads/

tenant_id/

trips/

trip_id/

expenses/
13. Despesas Rateáveis

Nem toda despesa precisa compor custo dos produtos.

Campo:

is_allocatable

Exemplo:

Sim

Hotel.

Passagem.

Transporte.

Não

Alimentação pessoal.

Compra particular.

14. Rateio de Custos

Objetivo:

Adicionar custos indiretos ao preço dos produtos.

Exemplo:

Compra:

100 produtos

Custo produtos:

US$10.000

Despesas:

Passagem:

R$3.000

Hotel:

R$4.000

Custo adicional:

R$7.000

Novo custo:

Produtos + despesas

R$57.000
15. Métodos de Rateio

O sistema deverá permitir:

Por quantidade

Divide igualmente.

Exemplo:

100 produtos

R$1.000 custo

Cada:

R$10

Por valor

Produtos mais caros recebem maior percentual.

Por peso

Para cargas.

Manual

Usuário define.

16. Tela de Rateio

Exemplo:

Produto       Valor Compra   Rateio

Notebook      R$5.000        R$500

Celular       R$3.000        R$300

Relógio       R$2.000        R$200
17. Produtos Vinculados

Uma viagem poderá possuir vários produtos.

Relacionamento:

Viagem

1:N

Compra Produtos

Exemplo:

Viagem:

Miami 2026

Produtos:

celulares;
notebooks;
relógios.
18. Importação de Produtos

Durante a viagem:

Usuário poderá cadastrar:

Produto

Quantidade

Preço USD

Fornecedor

Categoria

Ainda sem venda.

19. Fechamento da Viagem

Processo:

Usuário encerra viagem

↓

Sistema verifica despesas pendentes

↓

Calcula custos

↓

Calcula custo médio produtos

↓

Atualiza estoque

↓

Gera relatório
20. Validações no Fechamento

Não permitir fechar se:

existem despesas sem moeda;
existem produtos sem custo;
existem compras sem quantidade;
existem anexos pendentes obrigatórios.
21. Dashboard da Viagem

Mostrar:

Informações gerais
Destino

Datas

Status
Financeiro
Total gasto USD

Total convertido BRL

Despesas

Compras
Produtos
Quantidade comprada

Valor investido

Custo médio
22. Relatório da Viagem

Gerar PDF contendo:

Cabeçalho
ImportControl

Relatório de Importação
Dados da viagem
Destino

Período

Responsável
Despesas

Tabela:

Data

Categoria

Valor original

Conversão

BRL
Produtos

Tabela:

Produto

Quantidade

Custo USD

Custo BRL
Resumo
Investimento total

Custo médio

Margem prevista
23. Histórico de Alterações

Registrar:

criação;
edição;
exclusão;
fechamento.

Exemplo:

Usuário João

Alterou cotação:

5,35 → 5,40

Data:

01/08/2026 14:30
24. Regras de Negócio
Regra 1

Produto importado sempre pertence a uma viagem.

Regra 2

Custos devem manter cotação histórica.

Regra 3

Após fechamento:

alterações exigem permissão administrativa.

Regra 4

Toda despesa deve possuir moeda.

Regra 5

Toda conversão deve ser armazenada.

25. Serviços Backend

Criar:

TripService

ExpenseService

CurrencyService

CostAllocationService

ImportReportService
26. Repositories

Criar:

TripRepository

ExpenseRepository

ExchangeRateRepository

CostAllocationRepository
27. Controllers

Criar:

TripController

ExpenseController

ImportController

TripReportController
28. APIs Internas

Endpoints futuros:

GET /api/trips

POST /api/trips

GET /api/trips/{id}

POST /api/trips/{id}/expenses

POST /api/trips/{id}/close
29. Permissões Necessárias

Criar:

trips.view

trips.create

trips.edit

trips.delete

trips.close

expenses.create

expenses.edit

expenses.delete

expenses.view
30. Critérios de Aceitação

O módulo será considerado pronto quando:

[ ] Criar viagem

[ ] Editar viagem

[ ] Alterar status

[ ] Cadastrar despesas

[ ] Converter moedas

[ ] Anexar comprovantes

[ ] Vincular produtos

[ ] Realizar rateio

[ ] Fechar viagem

[ ] Gerar relatório

[ ] Registrar auditoria