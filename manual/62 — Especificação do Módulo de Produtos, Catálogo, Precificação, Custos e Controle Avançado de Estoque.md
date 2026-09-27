# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 62 — Especificação do Módulo de Produtos, Catálogo, Precificação, Custos e Controle Avançado de Estoque

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Cadastro completo de produtos, gestão de custos, precificação, estoque, inventário e análise de giro

---

# 1. Objetivo

O módulo de Produtos será responsável por centralizar todas as informações relacionadas às mercadorias comercializadas pelo negócio.

O objetivo é permitir:


Cadastro organizado dos produtos

Controle de custos reais

Formação inteligente de preços

Controle de estoque

Histórico de alterações

Análise de desempenho

Gestão de rentabilidade


---

# 2. Conceito Geral

O fluxo do produto será:


Cadastro produto

    ↓

Definição categoria

    ↓

Registro fornecedor

    ↓

Entrada por importação

    ↓

Formação custo real

    ↓

Definição preço venda

    ↓

Controle estoque

    ↓

Venda

    ↓

Análise lucro


---

# 3. Requisitos Funcionais

O módulo deverá permitir:


RF001 - Cadastrar produtos

RF002 - Editar produtos

RF003 - Organizar categorias

RF004 - Controlar custos

RF005 - Definir preços

RF006 - Controlar estoque

RF007 - Registrar movimentações

RF008 - Criar alertas

RF009 - Gerar relatórios

RF010 - Analisar rentabilidade


---

# 4. Estrutura do Produto

Cada produto deverá possuir:


Identificação

Descrição

Categoria

Marca

Fornecedor

Código interno

Código de barras

Fotos

Custos

Preço venda

Estoque

Status


---

# 5. Cadastro Básico

Campos:


Nome do produto

Descrição

Categoria

Marca

Modelo

SKU

Código de barras

Unidade de medida

Status


---

# 6. Tabela Products

Estrutura:

```sql
CREATE TABLE products (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT NOT NULL,

category_id BIGINT,

brand_id BIGINT,

supplier_id BIGINT,

sku VARCHAR(100),

barcode VARCHAR(100),

name VARCHAR(150) NOT NULL,

description TEXT,

unit VARCHAR(20),

status VARCHAR(30),

created_at TIMESTAMP,

updated_at TIMESTAMP

);
7. SKU

O sistema deverá gerar código interno único.

Exemplo:

IPH15-256-BLK

NOTE-DELL-I7

CAM-XIA-01
8. Código de Barras

Permitir:

Cadastro manual

Leitura scanner

Geração automática
9. Categorias

Organização:

Exemplo:

Eletrônicos

   Smartphones

   Notebooks

   Acessórios


Moda

   Roupas

   Calçados
10. Tabela Categories
CREATE TABLE categories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

parent_id BIGINT,

name VARCHAR(100),

description TEXT,

status VARCHAR(30),

created_at TIMESTAMP

);
11. Categorias Hierárquicas

Permitir:

Categoria

    ↓

Subcategoria

    ↓

Produto

Exemplo:

Eletrônicos

 └── Celulares

      └── iPhone
12. Marcas

Criar cadastro:

Apple

Samsung

Dell

Nike

Xiaomi

Tabela:

brands

Estrutura:

CREATE TABLE brands (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(100),

created_at TIMESTAMP

);
13. Fornecedores

Relacionar produtos com origem.

Informações:

Nome

País

Contato

Observações
14. Fotos dos Produtos

Permitir:

Imagem principal

Galeria

Upload múltiplo

Tabela:

product_images
CREATE TABLE product_images (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

product_id BIGINT,

path VARCHAR(255),

is_main BOOLEAN,

created_at TIMESTAMP

);
15. Controle de Custos

O sistema deverá armazenar:

Custo compra

Custo importação

Custo adicional

Custo médio

Último custo
16. Tabela Product Costs
CREATE TABLE product_costs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

product_id BIGINT,

cost_type VARCHAR(50),

amount DECIMAL(12,2),

reference_id BIGINT,

created_at TIMESTAMP

);
17. Custo Médio

Cálculo:

(Custo estoque atual + nova compra)

/

Quantidade total

Exemplo:

10 produtos

R$1.000 custo


Compra nova:

5 produtos

R$600


Novo custo médio:

R$106,66
18. Histórico de Custos

Registrar:

Valor anterior

Novo valor

Motivo

Usuário

Data

Tabela:

product_cost_history
19. Precificação

O sistema deverá permitir:

Preço manual

Preço sugerido

Margem percentual

Lucro desejado
20. Dados de Preço

Produto:

Custo:

R$1.000


Margem:

30%


Preço sugerido:

R$1.300
21. Tabela Product Prices
CREATE TABLE product_prices (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

product_id BIGINT,

cost_price DECIMAL(12,2),

sale_price DECIMAL(12,2),

minimum_price DECIMAL(12,2),

margin DECIMAL(5,2),

created_at TIMESTAMP

);
22. Preço Mínimo

Regra:

O vendedor não poderá vender abaixo do mínimo sem permissão.

Exemplo:

Preço venda:

R$1.500


Preço mínimo:

R$1.300


Venda por R$1.200:

Bloqueada
23. Alteração de Preço na Venda

Permitir:

Alterar preço

Aplicar desconto

Solicitar autorização
24. Controle de Estoque

O sistema deverá controlar:

Quantidade atual

Entradas

Saídas

Ajustes

Reservas

Inventário
25. Tabela Stock
CREATE TABLE stock (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

product_id BIGINT,

quantity DECIMAL(12,3),

reserved_quantity DECIMAL(12,3),

minimum_quantity DECIMAL(12,3),

updated_at TIMESTAMP

);
26. Movimentações

Toda alteração deve gerar histórico.

Tipos:

Entrada compra

Venda

Devolução

Ajuste manual

Perda
27. Tabela Stock Movements
CREATE TABLE stock_movements (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

product_id BIGINT,

type VARCHAR(30),

quantity DECIMAL(12,3),

reference_type VARCHAR(50),

reference_id BIGINT,

user_id BIGINT,

created_at TIMESTAMP

);
28. Inventário

Permitir:

Contagem física

Comparação sistema

Ajuste automático
29. Tabela Inventories
CREATE TABLE inventories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

description VARCHAR(150),

status VARCHAR(30),

created_at TIMESTAMP

);
30. Itens Inventário
CREATE TABLE inventory_items (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

inventory_id BIGINT,

product_id BIGINT,

system_quantity DECIMAL(12,3),

counted_quantity DECIMAL(12,3),

difference DECIMAL(12,3)

);
31. Estoque Mínimo

Cada produto poderá possuir:

Quantidade mínima

Quantidade ideal

Quantidade máxima
32. Alertas de Estoque

Exemplos:

Produto abaixo mínimo

Produto sem estoque

Produto parado

Excesso estoque
33. Produto Parado

Regra:

Produto sem venda por:

30 dias

60 dias

90 dias

Configurável.

34. Análise de Giro

Calcular:

Quantidade vendida

Período

Estoque médio

Fórmula:

Giro estoque

=

Venda período

/

Estoque médio
35. Classificação ABC

Classificar produtos:

Classe A

Maior faturamento


Classe B

Médio impacto


Classe C

Baixo impacto
36. Rentabilidade por Produto

Mostrar:

Preço venda

Custo

Lucro

Margem

Quantidade vendida

Lucro total
37. Produtos Mais Lucrativos

Ranking:

Maior lucro absoluto

Maior margem %

Mais vendidos
38. Produtos Relacionados

Permitir:

Produtos similares

Venda conjunta

Sugestão complementar

Exemplo:

Notebook

+

Mouse

+

Mochila
39. Serviços Backend

Criar:

ProductService

CategoryService

StockService

PricingService

InventoryService

CostService
40. Controllers

Criar:

ProductController

CategoryController

StockController

InventoryController

PricingController
41. API Produtos

Endpoints:

GET /api/v1/products

POST /api/v1/products

PUT /api/v1/products/{id}

DELETE /api/v1/products/{id}


GET /api/v1/products/{id}/stock

GET /api/v1/products/{id}/history
42. Permissões

Criar:

products.view

products.create

products.edit

products.delete

products.change_price

stock.adjust

inventory.manage
43. Auditoria

Registrar:

Alteração custo

Alteração preço

Exclusão produto

Ajuste estoque

Inventário
44. Regras de Negócio
Regra 1

Produto não pode ser vendido sem preço.

Regra 2

Produto não pode possuir estoque negativo.

Regra 3

Alteração de custo gera histórico.

Regra 4

Venda abaixo do mínimo exige permissão.

Regra 5

Exclusão física deve ser evitada.

45. Critérios de Aceitação
[ ] Cadastro produtos funcionando

[ ] Categorias funcionando

[ ] Marcas funcionando

[ ] Fotos funcionando

[ ] Custos funcionando

[ ] Preços funcionando

[ ] Estoque funcionando

[ ] Inventário funcionando

[ ] Alertas funcionando

[ ] Relatórios funcionando

[ ] Auditoria funcionando
Encerramento da Parte 62

O módulo de Produtos será o núcleo operacional do ImportControl.

Ele permitirá transformar uma simples lista de mercadorias em uma gestão profissional:

Controle de patrimônio

Controle de custos

Controle de estoque

Precificação inteligente

Análise de lucro

A estrutura está preparada para suportar milhares de produtos e múltiplas empresas dentro do modelo SaaS.