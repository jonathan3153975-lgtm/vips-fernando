# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 22 — Módulo de Produtos, Estoque e Formação de Preço

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação funcional do cadastro de produtos, controle de estoque, custos e precificação

---

# 1. Objetivo

O módulo de Produtos será responsável pelo gerenciamento completo das mercadorias comercializadas pelo usuário.

O objetivo é controlar:

- cadastro de produtos;
- categorias;
- fornecedores;
- custos de aquisição;
- estoque;
- movimentações;
- preço de venda;
- margem de lucro;
- histórico de alterações.

---

# 2. Conceito do Módulo

O ciclo do produto no sistema será:


Compra internacional

↓

Cadastro produto

↓

Definição custo real

↓

Entrada estoque

↓

Definição preço venda

↓

Venda

↓

Baixa estoque

↓

Análise lucro


---

# 3. Objetivo Financeiro

O sistema deverá responder:

- Quanto custou cada produto?
- Quantas unidades existem?
- Qual preço está sendo vendido?
- Qual lucro por unidade?
- Quais produtos possuem maior rentabilidade?

---

# 4. Menu Produtos

Estrutura:


Produtos

├── Produtos cadastrados

├── Categorias

├── Fornecedores

├── Estoque

├── Movimentações

└── Histórico de custos


---

# 5. Tela Principal de Produtos

Layout:


Produtos

[ Novo Produto ]

Busca

Nome

Categoria

Estoque

Custo

Venda

Margem

Status

Ações


---

# 6. Cadastro de Produto

Campos obrigatórios:


Nome

Categoria

Unidade

Custo

Preço venda

Estoque inicial


---

Campos adicionais:


Código interno

SKU

Código de barras

Marca

Modelo

Descrição

Imagem

Fornecedor


---

# 7. Estrutura do Produto

Exemplo:


Produto:

Apple AirPods Pro

Categoria:

Eletrônicos

Fornecedor:

Apple Store

Custo:

R$1.200

Venda:

R$1.800

Lucro:

R$600


---

# 8. Código do Produto

O sistema deverá permitir:

## Código automático

Exemplo:


PROD-000001


---

## Código manual

Usuário pode informar:


SKU-APPLE001


---

# 9. Código de Barras

Permitir:

- cadastro manual;
- leitura por scanner;
- geração futura.

---

Utilização:

- busca rápida;
- venda;
- inventário.

---

# 10. Categorias

Objetivo:

Organizar produtos.

---

Exemplos:


Eletrônicos

Informática

Roupas

Acessórios

Perfumes

Games


---

Tabela:


product_categories


---

Campos:

```sql
id

tenant_id

name

description

status

created_at
11. Fornecedores

Cadastrar:

Nome

País

Cidade

Contato

Email

Telefone

Observações

Exemplo:

Fornecedor:

Best Buy


País:

Estados Unidos
12. Produto Importado

Produtos comprados no exterior deverão possuir:

País origem

Moeda compra

Valor original

Cotação

Custo convertido

Exemplo:

Produto:

Notebook Dell


Compra:

USD 800


Cotação:

5,40


Custo:

R$4.320
13. Formação do Custo

O sistema deverá trabalhar com dois custos:

Custo de aquisição

Valor pago no fornecedor.

Exemplo:

USD 800
Custo real

Custo completo após despesas.

Exemplo:

Compra:

R$4.320


Rateio viagem:

R$150


Custo real:

R$4.470
14. Campos de Custo

Produto deverá armazenar:

Custo original

Moeda

Cotação utilizada

Custo convertido

Custos adicionais

Custo final
15. Histórico de Custos

Toda alteração deverá ser registrada.

Exemplo:

Produto:

Notebook Dell


Antes:

R$4.470


Depois:

R$4.650


Motivo:

Nova viagem

Tabela:

product_cost_history
16. Controle de Estoque

O estoque deverá controlar:

Quantidade atual

Quantidade mínima

Quantidade disponível

Quantidade reservada
17. Estoque Inicial

Ao cadastrar produto:

Usuário informa:

Quantidade inicial

Custo unitário

Sistema cria:

Movimentação de entrada
18. Movimentações de Estoque

Tipos:

ENTRADA

VENDA

DEVOLUÇÃO

AJUSTE

PERDA

TRANSFERÊNCIA
19. Entrada de Estoque

Exemplo:

Compra:

100 unidades

Movimentação:

+100 unidades

Registro:

Data

Produto

Quantidade

Origem

Usuário
20. Saída de Estoque

Normalmente ocorre por:

Venda

Exemplo:

Venda:

5 unidades

Movimento:

-5 unidades
21. Estoque Baixo

Criar alerta:

Exemplo:

Produto:

AirPods


Atual:

2 unidades


Mínimo:

10 unidades

Notificação:

Estoque abaixo do limite.
22. Inventário

Permitir conferência física.

Fluxo:

Iniciar inventário

↓

Contar produtos

↓

Comparar sistema

↓

Ajustar diferenças
23. Ajuste de Estoque

Obrigatório informar:

Produto

Quantidade anterior

Nova quantidade

Motivo

Motivos:

Erro cadastro

Perda

Roubo

Contagem física
24. Valorização do Estoque

Sistema deverá calcular:

Quantidade × Custo real

Exemplo:

100 produtos

Custo:

R$500


Valor estoque:

R$50.000
25. Formação do Preço de Venda

O sistema deverá permitir:

Manual

Usuário define.

Exemplo:

Venda:

R$900
Automático por margem

Usuário define:

Margem desejada:
40%

Sistema calcula:

Custo:

R$500


Venda:

R$700
26. Fórmula de Margem

Lucro:

Preço venda - Custo

Margem:

Lucro ÷ Preço venda × 100

Exemplo:

Venda:

R$1000


Custo:

R$700


Lucro:

R$300


Margem:

30%
27. Precificação Inteligente

Preparar futuras regras:

Concorrência

Comparar preços.

Histórico

Analisar vendas anteriores.

Demanda

Produtos mais procurados.

28. Alteração de Preço no Momento da Venda

Regra importante:

O vendedor poderá alterar o preço durante uma venda.

Exemplo:

Produto:

Custo:

R$500


Preço padrão:

R$900

Venda:

Preço negociado:

R$850

O sistema deverá registrar:

Preço original

Preço vendido

Desconto aplicado
29. Proteção Contra Venda Prejuízo

Configurar:

Permitir abaixo do custo?

SIM/NÃO

Caso negativo:

Exibir:

Atenção:

Preço abaixo do custo.
Confirme autorização.
30. Produtos Mais Rentáveis

Dashboard:

Mostrar:

Produto

Quantidade vendida

Lucro total

Margem
31. Produtos Parados

Identificar:

Produto sem venda há X dias

Exemplo:

Sem vendas:

90 dias

Sugestão:

Criar promoção
32. Produtos Mais Vendidos

Ranking:

1º Produto A

2º Produto B

3º Produto C
33. Importação em Massa

Permitir:

Importar:

CSV

Excel

Campos:

Nome

Categoria

Custo

Venda

Estoque
34. Exportação

Permitir:

Exportar:

Lista produtos

Estoque

Custos

Preços
35. Banco de Dados
products
id

tenant_id

category_id

supplier_id

sku

barcode

name

description

image

cost_price

sale_price

minimum_stock

status

created_at

updated_at

deleted_at
stock
id

tenant_id

product_id

quantity

minimum_quantity

updated_at
stock_movements
id

tenant_id

product_id

type

quantity

reference_type

reference_id

user_id

created_at
36. Services Backend

Criar:

ProductService

StockService

PricingService

InventoryService

CostService
37. Controllers

Criar:

ProductController

CategoryController

SupplierController

StockController

InventoryController
38. Regras de Negócio
Regra 1

Produto vendido sempre gera baixa no estoque.

Regra 2

Toda entrada deve possuir origem.

Regra 3

Alteração de custo gera histórico.

Regra 4

Preço vendido deve ser armazenado independente do preço atual.

Regra 5

Produto excluído deve utilizar Soft Delete.

39. Auditoria

Registrar:

alteração custo;
alteração preço;
ajuste estoque;
exclusão;
importação.
40. Critérios de Aceitação
[ ] Cadastro produtos

[ ] Categorias

[ ] Fornecedores

[ ] Controle estoque

[ ] Entrada produtos

[ ] Saída produtos

[ ] Inventário

[ ] Histórico custos

[ ] Formação preço

[ ] Margem lucro

[ ] Importação CSV

[ ] Relatórios
Encerramento da Parte 22

O módulo de Produtos e Estoque será o núcleo operacional do ImportControl.

Ele permitirá que o vendedor tenha controle real sobre:

investimento;
mercadorias disponíveis;
custo verdadeiro;
preço adequado;
lucro obtido.

A estrutura foi pensada para atender desde um pequeno vendedor individual até uma operação SaaS com múltiplas empresas.