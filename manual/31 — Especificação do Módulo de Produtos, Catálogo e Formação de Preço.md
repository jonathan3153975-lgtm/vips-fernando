# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 31 — Especificação do Módulo de Produtos, Catálogo e Formação de Preço

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Cadastro de produtos, controle de custos, precificação, histórico e integração comercial

---

# 1. Objetivo

O módulo de Produtos será o núcleo operacional do ImportControl.

Ele será responsável por controlar:

- catálogo de mercadorias;
- produtos importados;
- custos em moeda estrangeira;
- conversão cambial;
- preço de venda;
- margem de lucro;
- histórico de alterações;
- integração com estoque e vendas.

---

# 2. Conceito do Produto

No negócio do usuário, o produto possui uma característica especial:


Compra internacional

↓

Custo em dólar

↓

Conversão para real

↓

Custos adicionais

↓

Preço final de venda


---

Exemplo:


Produto:

Smartwatch X

Compra:

USD 100

Cotação:

R$5,20

Custo convertido:

R$520

Preço venda:

R$799


---

# 3. Objetivos do Módulo

O sistema deverá permitir:


Cadastrar produtos

Controlar custos

Definir preços

Alterar preço na venda

Calcular lucro

Controlar estoque

Analisar rentabilidade


---

# 4. Menu Produtos

Estrutura:


Produtos

├── Lista de produtos

├── Novo produto

├── Categorias

├── Fornecedores

├── Histórico de custos

├── Histórico de preços

└── Importação de produtos


---

# 5. Cadastro de Produto

Campos principais:


Nome

Categoria

Fornecedor

Código interno

Código de barras

Descrição

Imagem

Status


---

# 6. Identificação do Produto

Campos:

## Código interno

Exemplo:


AIRPODS-PRO-001


---

## Código de barras

Permitir:


EAN

UPC

Outro identificador


---

# 7. Categorias de Produtos

Objetivo:

Organizar catálogo.

Exemplos:


Eletrônicos

Informática

Celulares

Acessórios

Games

Perfumes

Roupas


---

Tabela:

## product_categories

Campos:

```sql
id

tenant_id

name

description

status

created_at

updated_at
8. Fornecedores

Permitir vincular:

Produto

↓

Fornecedor

Dados:

Nome

País

Cidade

Contato

Telefone

Email

Observações
9. Produtos Importados

Possuir campos específicos:

País origem

Moeda compra

Valor compra

Cotação utilizada

Data compra
10. Custo em Moeda Estrangeira

O sistema deverá aceitar:

Exemplo:

Produto:

Notebook


Valor compra:

USD 500

Campos:

foreign_currency_id

foreign_cost

exchange_rate

converted_cost
11. Conversão Automática

Fórmula:

Valor em moeda estrangeira

×

Cotação

=

Custo convertido

Exemplo:

USD 500

×

5,20

=

R$2.600
12. Custos Adicionais

O custo real poderá incluir:

Produto

Frete

Impostos

Taxas

Seguro

Outros custos

Fórmula:

Custo real

=

Produto

+

Custos adicionais
13. Formação do Custo Final

Exemplo:

Produto:

USD 300

Conversão:

R$1.560

Custos:

Frete:

R$100


Taxas:

R$50

Custo final:

R$1.710
14. Campos de Custo

Tabela products:

Adicionar:

cost_currency_id

cost_foreign

exchange_rate

cost_product

cost_shipping

cost_tax

cost_total
15. Preço de Venda

Campos:

Preço sugerido

Preço mínimo

Preço atual

Exemplo:

Custo:

R$500


Venda sugerida:

R$800


Venda mínima:

R$700
16. Cálculo de Margem

Fórmula:

Margem %

=

((Venda - Custo) / Venda) × 100

Exemplo:

Venda:

R$1.000


Custo:

R$600


Margem:

40%
17. Formação Automática de Preço

Permitir configurar:

Margem desejada

Exemplo:

Configuração:

Margem:

50%

Sistema calcula:

Custo:

R$500


Preço sugerido:

R$1.000
18. Regras de Precificação

Permitir:

Margem fixa

Exemplo:

Produto sempre +40%
Multiplicador

Exemplo:

Custo x 2
Preço manual

Usuário define.

19. Alteração de Preço na Venda

Durante venda:

Sistema mostra:

Preço padrão:

R$900

Usuário autorizado pode alterar:

Preço aplicado:

R$850

Registrar:

Preço original

Preço vendido

Diferença

Usuário

Data
20. Controle de Desconto

Permitir:

Desconto percentual

Desconto valor fixo

Exemplo:

Preço:

R$1.000


Desconto:

10%


Final:

R$900
21. Limite de Desconto

Configuração:

Administrador define limite

Exemplo:

Vendedor:

Máximo 10%

Gerente:

Máximo 30%
22. Histórico de Custos

Toda alteração deverá ser registrada.

Tabela:

product_cost_history
id

tenant_id

product_id

old_cost

new_cost

reason

user_id

created_at

Exemplo:

Custo antigo:

R$500


Novo:

R$550


Motivo:

Nova compra
23. Histórico de Preços

Criar:

product_price_history
id

tenant_id

product_id

old_price

new_price

reason

user_id

created_at

Motivos:

Aumento dólar

Promoção

Mercado

Nova margem
24. Status do Produto

Estados:

ACTIVE

INACTIVE

DISCONTINUED

OUT_OF_STOCK
25. Produto Favorito

Permitir marcar:

Produtos estratégicos

Uso:

Dashboard:

Produtos importantes
26. Produto com Variações

Preparar estrutura:

Exemplo:

Camiseta

Tamanho:

P,M,G


Cor:

Azul,Preta

Tabela futura:

product_variations
id

product_id

attribute

value
27. Imagens dos Produtos

Permitir:

Imagem principal

Galeria

Regras:

validar extensão;
limitar tamanho;
gerar miniaturas.
28. Importação em Massa

Permitir:

CSV

Excel

Campos:

Nome

Categoria

Custo

Preço

Estoque
29. Duplicação de Produto

Permitir:

Duplicar produto

Útil para:

Produtos semelhantes
30. Busca de Produtos

Filtros:

Nome

Código

Categoria

Fornecedor

Status
31. Tela Lista de Produtos

Layout:

Produtos


[ Novo produto ]


Busca


Tabela


Imagem

Nome

Categoria

Custo

Venda

Margem

Estoque

Ações
32. Tela Detalhes do Produto

Exibir:

Imagem

Informações

Custos

Preços

Estoque

Vendas

Histórico
33. Dashboard do Produto

Mostrar:

Quantidade vendida

Receita gerada

Lucro total

Última venda

Giro estoque
34. Integração com Estoque

Eventos:

Entrada:

Compra recebida

+

Estoque

Saída:

Venda

-

Estoque
35. Integração com Financeiro

Compras:

Geram custo

Vendas:

Geram receita
36. Banco de Dados Complementar
products
id

tenant_id

category_id

supplier_id

sku

name

description

cost_currency_id

cost_foreign

exchange_rate

cost_total

sale_price

minimum_price

margin

status

created_at

updated_at

deleted_at
product_images
id

product_id

path

is_main

created_at
product_price_history
id

tenant_id

product_id

old_price

new_price

reason

user_id

created_at
37. Services Backend

Criar:

ProductService

PriceService

CostService

CategoryService

SupplierService

ProductImageService
38. Controllers

Criar:

ProductController

CategoryController

SupplierController

PriceController
39. Regras de Segurança

Controlar:

Quem pode visualizar custo

Quem pode alterar preço

Quem pode excluir produto

Exemplo:

Vendedor:

Não vê custo real

Administrador:

Vê todos dados
40. Critérios de Aceitação
[ ] Cadastro completo de produtos

[ ] Cadastro categorias

[ ] Cadastro fornecedores

[ ] Custo em dólar

[ ] Conversão cambial

[ ] Formação preço venda

[ ] Controle margem

[ ] Histórico alterações

[ ] Controle permissões

[ ] Integração estoque

[ ] Integração vendas
Encerramento da Parte 31

O módulo de Produtos será a base comercial do ImportControl.

Ele foi projetado considerando a realidade do negócio:

Comprar em dólar

↓

Controlar custo real

↓

Definir preço estratégico

↓

Vender em real

↓

Medir lucro

Este módulo permitirá que o proprietário saiba exatamente quanto cada mercadoria custa e quanto realmente está ganhando.