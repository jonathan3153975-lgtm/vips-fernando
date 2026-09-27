# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 12 — Módulo de Produtos, Compras, Estoque e Formação de Preço

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação funcional e técnica do gerenciamento de produtos, estoque e precificação

---

# 1. Objetivo

O módulo de Produtos será responsável por controlar todo o ciclo dos itens comercializados pelo negócio.

Desde:


Compra no exterior

↓

Entrada no estoque

↓

Cálculo do custo real

↓

Definição de preço

↓

Venda

↓

Baixa do estoque

↓

Análise de lucro


---

O módulo deverá permitir que o proprietário saiba:

- quanto pagou pelo produto;
- quanto realmente custou após despesas;
- qual margem possui;
- qual preço mínimo de venda;
- quantidade disponível;
- histórico de movimentações.

---

# 2. Conceito do Produto

No ImportControl, um produto possui dois valores principais:

## Custo de aquisição

Valor pago na compra.

Exemplo:


Produto:

iPhone

Compra:

USD 500


---

## Custo real

Valor final considerando:

- conversão cambial;
- despesas de viagem;
- transporte;
- taxas;
- outros custos.

Exemplo:


Compra:

R$2.700

Rateio:

R$300

Custo real:

R$3.000


---

# 3. Estrutura do Módulo


Produtos

├── Cadastro

├── Categorias

├── Fornecedores

├── Compras

├── Lotes

├── Estoque

├── Custos

├── Preços

├── Inventário

└── Relatórios


---

# 4. Cadastro de Produto

Campos principais:


Nome

Código interno

SKU

Código de barras

Categoria

Marca

Modelo

Descrição

Foto

Peso

Unidade

Status


---

Exemplo:


Nome:

Apple iPhone 15 Pro

Categoria:

Celulares

Marca:

Apple

SKU:

IPH15PRO256


---

# 5. SKU

Todo produto deverá possuir código único.

Exemplo:


IPH15PRO256-USA


---

Objetivos:

- localização;
- controle;
- integração futura;
- inventário.

---

# 6. Código de Barras

Permitir:

- cadastro manual;
- geração automática;
- leitura por scanner.

---

Possíveis formatos:


EAN-13

CODE128

QR Code


---

# 7. Categorias

Permitir organização.

Exemplos:


Eletrônicos

Celulares

Informática

Games

Acessórios

Roupas

Perfumaria


---

Tabela:


product_categories


---

# 8. Marcas

Cadastro opcional.

Exemplos:


Apple

Samsung

Sony

Nike


---

Objetivo:

Relatórios por marca.

---

# 9. Fornecedores

Um produto poderá possuir fornecedor.

Dados:


Nome

País

Contato

Site

Observações


---

Exemplo:


Fornecedor:

Miami Electronics Store

País:

USA


---

# 10. Compras de Produtos

Toda entrada deverá estar vinculada a:


Viagem

Fornecedor

Produto

Quantidade

Moeda

Valor unitário

Cotação

Custo convertido


---

Exemplo:

Compra:


Produto:

Notebook

Quantidade:

10

Valor:

USD 800

Cotação:

5,40

Total:

R$43.200


---

# 11. Lotes de Importação

Cada compra deverá gerar um lote.

Exemplo:


Lote:

MIAMI-2026-001


---

Informações:


Data compra

Viagem

Fornecedor

Produtos


---

Benefícios:

- rastrear origem;
- calcular custos;
- analisar rentabilidade.

---

# 12. Formação do Custo Real

O custo deverá considerar:


Preço compra

Cotação

Despesas rateadas

Custos adicionais

=

Custo Real


---

Exemplo:

Produto:


USD 100


Cotação:


5,40


Compra:


R$540


Rateio:


R$60


Custo final:


R$600


---

# 13. Histórico de Custo

Nunca sobrescrever custos antigos.

Manter histórico:


Produto

Data

Custo anterior

Novo custo

Usuário


---

Exemplo:


01/01

Custo:

R$600

05/02

Custo:

R$650


---

# 14. Preço de Venda

O sistema deverá permitir:

## Preço manual

Usuário define.

---

## Preço automático

Baseado em margem.

---

Exemplo:

Custo:


R$600


Margem:


30%


Preço:


R$780


---

# 15. Margem de Lucro

Fórmula:


Lucro = Venda - Custo


---

Margem:


(Venda - Custo) / Venda * 100


---

Exemplo:

Venda:


R$1.000


Custo:


R$700


Lucro:


R$300


Margem:


30%


---

# 16. Preço Mínimo

Sistema deverá calcular:


Custo + margem mínima


---

Exemplo:

Custo:


R$500


Margem mínima:


20%


Preço mínimo:


R$625


---

# 17. Alteração de Preço na Venda

O vendedor poderá alterar preço no momento da venda.

Porém deverá registrar:


Preço original

Preço vendido

Usuário

Data

Motivo


---

Exemplo:


Preço tabela:

R$1.000

Venda:

R$900

Motivo:

Cliente antigo


---

# 18. Controle de Estoque

O estoque será controlado através de movimentações.

Nunca alterar quantidade diretamente.

---

Fluxo:


Compra

↓

Entrada estoque

↓

Venda

↓

Saída estoque


---

# 19. Movimentações de Estoque

Tipos:


ENTRY

SALE

RETURN

LOSS

ADJUSTMENT


---

Exemplo:

Entrada:


+10 notebooks


Venda:


-1 notebook


---

# 20. Histórico de Estoque

Registrar:


Produto

Quantidade anterior

Movimento

Quantidade alterada

Quantidade final

Usuário

Data


---

# 21. Inventário

Permitir conferência física.

Fluxo:


Iniciar inventário

↓

Contagem

↓

Comparar sistema

↓

Ajustar diferenças


---

# 22. Ajuste de Estoque

Somente usuários autorizados.

Motivos:


Produto perdido

Erro cadastro

Dano

Conferência física


---

# 23. Alertas de Estoque

Configurar:

Estoque mínimo.

Exemplo:


Produto:

iPhone

Mínimo:

3 unidades


---

Quando:


Estoque <= 3


Gerar alerta.

---

# 24. Tela de Produtos

Layout:


Produtos

[ Novo Produto ]

Busca

Filtros

Foto

Nome

Categoria

Custo

Venda

Margem

Estoque

Ações


---

# 25. Página de Detalhes do Produto

Exibir:

## Informações


Nome

SKU

Categoria

Foto


---

## Financeiro


Custo atual

Preço venda

Lucro

Margem


---

## Estoque


Quantidade atual

Entradas

Saídas


---

## Histórico


Compras

Alterações

Vendas


---

# 26. Tela de Compra

Campos:


Produto

Quantidade

Valor moeda origem

Cotação

Valor convertido

Viagem

Fornecedor


---

# 27. Importação em Massa

Futura funcionalidade.

Permitir:


Arquivo CSV

Excel

Planilha


---

Uso:

Grandes compras.

---

# 28. Fotos dos Produtos

Permitir:

- imagem principal;
- imagens adicionais.

---

Armazenamento:


storage/uploads/

tenant/

products/

produto_id/


---

# 29. Relatórios de Produtos

Disponíveis:

## Produtos mais lucrativos

Ordenar:

Lucro.

---

## Produtos com maior estoque

Quantidade.

---

## Produtos parados

Sem venda.

---

## Evolução de custo

Histórico.

---

# 30. Serviços Backend

Criar:


ProductService

StockService

PurchaseService

PricingService

InventoryService

CostService


---

# 31. Repositories

Criar:


ProductRepository

CategoryRepository

StockRepository

PurchaseRepository

InventoryRepository


---

# 32. Controllers

Criar:


ProductController

StockController

PurchaseController

InventoryController


---

# 33. Permissões Necessárias

Adicionar:


products.view

products.create

products.edit

products.delete

products.cost.view

stock.view

stock.adjust

purchase.create

purchase.view

inventory.manage


---

# 34. Eventos do Sistema

Criar eventos:


ProductCreated

StockUpdated

CostChanged

PriceChanged


---

# 35. Auditoria

Registrar:

Alteração de custo.

Alteração de preço.

Exclusão.

Ajuste de estoque.

---

# 36. Regras de Negócio

## Regra 1

Produto vendido nunca deve ser excluído fisicamente.

---

## Regra 2

Estoque deve sempre ser resultado das movimentações.

---

## Regra 3

Custo histórico nunca deve ser alterado.

---

## Regra 4

Toda venda deve gerar baixa de estoque.

---

## Regra 5

Toda entrada deve possuir origem.

---

# 37. Critérios de Aceitação


[ ] Cadastro de produto

[ ] Cadastro de categorias

[ ] Cadastro de fornecedores

[ ] Compra internacional

[ ] Conversão cambial

[ ] Formação de custo real

[ ] Controle estoque

[ ] Histórico movimentações

[ ] Formação de preço

[ ] Alteração na venda

[ ] Inventário

[ ] Relatórios


---

# Encerramento da Parte 12

O módulo de Produtos é o coração operacional do ImportControl.

Ele transforma dados de compra internacional em informações reais de negócio:

- custo verdadeiro;
- estoque confiável;
- preço adequado;
- lucro conhecido.

---

## Próxima Parte (Parte 13)

# Módulo de Vendas, Clientes, Pagamentos e Controle Comercial

Será detalhado:

- cadastro de clientes;
- processo de venda;
- carrinho;
- alteração de preço;
- descontos;
- formas de pagamento;
- parcelamentos;
- comissão;
- baixa de estoque;
- lucro por venda;
- histórico comercial.