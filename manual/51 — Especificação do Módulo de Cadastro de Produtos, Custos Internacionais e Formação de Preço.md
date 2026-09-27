# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 51 — Especificação do Módulo de Cadastro de Produtos, Custos Internacionais e Formação de Preço

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Cadastro de produtos, controle de custos, conversão cambial, precificação e regras comerciais

---

# 1. Objetivo

O módulo de Produtos será responsável por centralizar todas as informações relacionadas às mercadorias comercializadas pelo negócio.

O módulo deverá permitir:


Cadastro completo de produtos

Controle de custos internacionais

Registro de compras em moeda estrangeira

Conversão automática para Real

Formação de preço de venda

Controle de margem

Histórico de alterações

Integração com estoque e vendas


---

# 2. Conceito Geral

O fluxo principal do produto será:


Compra no exterior

    ↓

Registro da importação

    ↓

Custo em moeda estrangeira

    ↓

Conversão cambial

    ↓

Cálculo custo real

    ↓

Definição preço venda

    ↓

Entrada estoque

    ↓

Venda ao cliente


---

# 3. Requisitos Funcionais

O sistema deverá permitir:


RF001 - Cadastrar produtos

RF002 - Editar produtos

RF003 - Inativar produtos

RF004 - Controlar categorias

RF005 - Registrar custos

RF006 - Definir preço venda

RF007 - Calcular margem

RF008 - Controlar histórico

RF009 - Integrar estoque

RF010 - Integrar vendas


---

# 4. Cadastro do Produto

Cada produto deverá possuir:


Código interno

Nome

Descrição

Categoria

Marca

Modelo

SKU

Código de barras

Imagem

Unidade de venda

Status


---

# 5. Tela Cadastro Produto

Layout:


Novo Produto

Informações básicas

Nome:

[________________]

Categoria:

[________________]

Marca:

[________________]

SKU:

[________________]

Código barras:

[________________]

Imagem:

[ Upload ]

[Salvar Produto]


---

# 6. Código Interno

O sistema deverá gerar automaticamente:

Exemplo:


PROD-000001

PROD-000002

PROD-000003


---

Possibilidade:


Código automático

Código manual


---

# 7. Categorias

Permitir organizar produtos:

Exemplos:


Eletrônicos

Celulares

Informática

Acessórios

Games

Outros


---

Tabela:

```sql
product_categories

Relacionamento:

Categoria

1:N

Produtos
8. Marcas

Cadastro:

Nome

Descrição

Status

Exemplo:

Apple

Samsung

Xiaomi

Lenovo
9. Unidade de Venda

Permitir:

Unidade

Par

Kit

Caixa

Pacote

Tabela:

units
10. Produto com Variações

Preparar arquitetura para:

Cor

Tamanho

Modelo

Capacidade

Exemplo:

iPhone 15

128GB Preto

256GB Azul

Tabela futura:

product_variations
11. Imagens do Produto

Permitir:

Imagem principal

Galeria

Fotos adicionais

Regras:

Formatos:

JPG

PNG

WEBP


Tamanho máximo configurável
12. Custos Internacionais

O sistema deverá permitir informar:

Valor compra estrangeiro

Moeda

Taxa câmbio

Data compra

Fornecedor

Viagem relacionada

Exemplo:

Compra:

Produto:

Notebook Dell


Valor:

US$ 500


Cotação:

R$ 5,20


Custo convertido:

R$ 2.600
13. Tabela Product Costs

Criar:

CREATE TABLE product_costs (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

product_id BIGINT,

currency_id BIGINT,

foreign_value DECIMAL(12,2),

exchange_rate DECIMAL(12,4),

converted_value DECIMAL(12,2),

purchase_date DATE,

created_at TIMESTAMP

);
14. Conversão Cambial

Fórmula:

Custo Real

=

Valor Estrangeiro

×

Cotação moeda

Exemplo:

US$ 300

×

5,10

=

R$ 1.530
15. Histórico Cambial

Guardar:

Cotação utilizada

Data

Usuário responsável

Origem informação

Motivo:

Permitir auditoria

Recalcular custos

Comparar compras
16. Cotação Automática

Preparar integração futura:

APIs:

Banco Central

APIs financeiras

Serviços cambiais

O usuário poderá escolher:

Cotação automática

Cotação manual
17. Custos Adicionais

O custo final não será apenas compra.

Adicionar:

Frete internacional

Impostos

Taxas

Seguro

Transporte

Despachante

Outros
18. Formação do Custo Real

Fórmula:

Custo Produto

=

Compra produto

+

Frete

+

Impostos

+

Despesas relacionadas

Exemplo:

Produto:

R$ 2.000


Frete:

R$ 100


Taxas:

R$ 50


Custo final:

R$ 2.150
19. Rateio de Despesas

Quando uma viagem possuir vários produtos:

O sistema deverá permitir:

Rateio proporcional

Rateio manual

Exemplo:

Viagem:

Despesa total:

R$ 5.000

Produtos:

Produto A

70%


Produto B

30%
20. Formação do Preço de Venda

Métodos disponíveis:

Margem percentual

Markup

Preço manual
21. Formação por Margem

Fórmula:

Preço Venda

=

Custo

/

(1 - margem)

Exemplo:

Custo:

R$100


Margem:

40%


Preço:

R$166,67
22. Formação por Markup

Fórmula:

Preço

=

Custo × Markup

Exemplo:

Custo:

R$100


Markup:

2


Venda:

R$200
23. Campos de Precificação

Produto deverá possuir:

Custo médio

Último custo

Preço venda

Preço mínimo

Margem atual
24. Preço Mínimo

Regra:

Nunca permitir venda abaixo:

Preço mínimo definido

Exemplo:

Preço normal:

R$500


Preço mínimo:

R$450

Caso usuário tente:

Venda R$400

↓

Solicitar autorização
25. Alteração de Preço na Venda

O vendedor poderá alterar:

Preço venda

Desconto

Conforme permissão.

Fluxo:

Venda

↓

Alterar preço

↓

Validar limite

↓

Registrar motivo
26. Controle de Desconto

Criar regras:

Administrador:

Sem limite


Usuário:

Até percentual definido

Exemplo:

Desconto máximo:

10%
27. Histórico de Preços

Registrar:

Produto

Preço anterior

Novo preço

Usuário

Data

Motivo

Tabela:

product_price_history
28. Margem de Lucro

Calcular:

Lucro

=

Venda

-

Custo

Percentual:

Margem

=

Lucro

/

Venda
29. Indicadores do Produto

Mostrar:

Quantidade vendida

Lucro gerado

Margem média

Última venda

Última compra
30. Alertas de Produto

Criar alertas:

Produto sem custo

Produto sem preço

Margem baixa

Estoque baixo

Produto parado
31. Status do Produto

Estados:

ACTIVE

INACTIVE

DISCONTINUED

OUT_OF_STOCK
32. Busca de Produtos

Permitir buscar por:

Nome

SKU

Código barras

Categoria

Marca
33. Tela Lista Produtos

Layout:

Produtos


[Buscar]


---------------------------------

Código

Nome

Categoria

Custo

Venda

Estoque

Margem

Ações

---------------------------------
34. Ações Rápidas

Disponibilizar:

Editar

Duplicar

Alterar preço

Visualizar histórico

Ver estoque

Vender
35. Duplicação de Produto

Permitir:

Criar produto semelhante

Copiar informações

Alterar dados específicos
36. Integração com Estoque

Ao cadastrar produto:

Produto criado

↓

Disponível para entrada estoque

Ao vender:

Venda confirmada

↓

Baixa estoque automática
37. Integração com Compras Internacionais

Produto poderá estar vinculado:

Viagem

Importação

Fornecedor

Documento compra
38. Relatórios de Produtos

Criar:

Produtos mais vendidos

Produtos mais lucrativos

Produtos parados

Produtos sem margem

Histórico custos
39. API Produtos

Preparar endpoints:

GET    /api/v1/products

POST   /api/v1/products

PUT    /api/v1/products/{id}

DELETE /api/v1/products/{id}

GET    /api/v1/products/{id}/history
40. Services Backend

Criar:

ProductService

PricingService

CostCalculationService

StockIntegrationService
41. Regras de Negócio
Regra 1

Produto deve possuir custo antes de venda.

Regra 2

Venda abaixo do mínimo exige permissão.

Regra 3

Alterações de custo geram histórico.

Regra 4

Produtos vendidos nunca devem ser apagados.

42. Critérios de Aceitação
[ ] Cadastro produto funcionando

[ ] Categorias funcionando

[ ] Marcas funcionando

[ ] Custos em dólar funcionando

[ ] Conversão cambial funcionando

[ ] Formação preço automática

[ ] Controle margem

[ ] Histórico preços

[ ] Integração estoque

[ ] Integração vendas

[ ] Permissões aplicadas

[ ] Auditoria funcionando
Encerramento da Parte 51

O módulo de produtos será o núcleo comercial do ImportControl.

Ele deverá transformar uma simples lista de mercadorias em uma ferramenta estratégica capaz de responder:

Quanto paguei?

Quanto realmente custou?

Qual meu lucro?

Qual preço ideal?

Quais produtos devo vender mais?

A arquitetura permitirá controle completo desde a compra internacional até a venda final.