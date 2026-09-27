# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 53 — Especificação do Módulo de Estoque, Movimentações e Controle de Inventário

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Gestão de estoque, entradas, saídas, inventário, custo médio e integração com operações comerciais

---

# 1. Objetivo

O módulo de Estoque será responsável pelo controle físico e financeiro das mercadorias disponíveis para venda.

O objetivo é garantir que o proprietário tenha controle sobre:


Quantidade disponível

Localização dos produtos

Entradas e saídas

Produtos vendidos

Produtos recebidos do exterior

Ajustes manuais

Histórico completo

Valor do estoque atual


---

# 2. Conceito Geral

O estoque será integrado aos principais módulos:


Importações

  ↓

Entrada estoque

Compras

  ↓

Entrada estoque

Vendas

  ↓

Baixa estoque

Ajustes

  ↓

Movimentação manual


---

# 3. Requisitos Funcionais

O módulo deverá permitir:


RF001 - Controlar saldo dos produtos

RF002 - Registrar entradas

RF003 - Registrar saídas

RF004 - Registrar ajustes

RF005 - Controlar inventário

RF006 - Controlar custo médio

RF007 - Controlar estoque mínimo

RF008 - Gerar alertas

RF009 - Gerar relatórios

RF010 - Integrar vendas e importações


---

# 4. Conceito de Estoque

Cada produto possuirá:


Quantidade atual

Quantidade reservada

Quantidade disponível

Custo médio

Último custo

Valor total estoque


---

# 5. Estrutura do Estoque

Modelo:


Produto

  ↓

Local de armazenamento

  ↓

Quantidade disponível

  ↓

Movimentações


---

# 6. Locais de Estoque

O sistema deverá permitir múltiplos locais:

Exemplo:


Estoque principal

Sala comercial

Casa

Depósito

Armário A

Armário B


---

# 7. Tabela Warehouses

```sql
CREATE TABLE warehouses (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT NOT NULL,

name VARCHAR(100),

description TEXT,

address TEXT,

status ENUM(
'ACTIVE',
'INACTIVE'
),

created_at TIMESTAMP

);
8. Estoque por Produto

Tabela:

warehouse_stock

Estrutura:

CREATE TABLE warehouse_stock (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

warehouse_id BIGINT,

product_id BIGINT,

quantity DECIMAL(12,3),

reserved_quantity DECIMAL(12,3),

average_cost DECIMAL(12,2),

updated_at TIMESTAMP

);
9. Quantidade Disponível

Fórmula:

Estoque disponível

=

Quantidade atual

-

Quantidade reservada

Exemplo:

Quantidade:

100 unidades


Reservadas:

20 unidades


Disponível:

80 unidades
10. Tipos de Movimentação

O sistema deverá trabalhar com:

ENTRADA

SAÍDA

AJUSTE POSITIVO

AJUSTE NEGATIVO

TRANSFERÊNCIA

DEVOLUÇÃO
11. Tabela Stock Movements
CREATE TABLE stock_movements (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

warehouse_id BIGINT,

product_id BIGINT,

type VARCHAR(50),

quantity DECIMAL(12,3),

previous_quantity DECIMAL(12,3),

new_quantity DECIMAL(12,3),

reference_type VARCHAR(50),

reference_id BIGINT,

reason TEXT,

user_id BIGINT,

created_at TIMESTAMP

);
12. Entrada de Estoque

Origem:

Importação

Compra nacional

Devolução cliente

Ajuste manual

Processo:

Entrada registrada

↓

Quantidade atualizada

↓

Custo recalculado

↓

Histórico criado
13. Entrada por Importação

Quando uma viagem for finalizada:

Importação concluída

↓

Sistema gera entrada automática

↓

Atualiza estoque

↓

Atualiza custo médio
14. Saída de Estoque

Origem:

Venda

Perda

Ajuste

Uso interno

Processo:

Solicitação saída

↓

Validação quantidade

↓

Baixa estoque

↓

Registro histórico
15. Reserva de Estoque

Preparar arquitetura para vendas futuras.

Fluxo:

Pedido criado

↓

Produto reservado

↓

Pagamento confirmado

↓

Baixa definitiva
16. Controle de Estoque Negativo

Regra padrão:

Não permitir estoque negativo

Exceção:

Administrador pode configurar:

Permitir venda sem estoque

Sim / Não
17. Ajuste Manual

Permitir:

Adicionar quantidade

Remover quantidade

Corrigir divergência

Obrigatório informar:

Motivo

Usuário

Data
18. Inventário

O sistema deverá permitir conferência física.

Fluxo:

Criar inventário

↓

Contar produtos

↓

Informar quantidade real

↓

Comparar sistema

↓

Gerar ajustes
19. Tabela Inventories
CREATE TABLE inventories (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

warehouse_id BIGINT,

status VARCHAR(30),

started_at DATETIME,

finished_at DATETIME,

user_id BIGINT

);
20. Itens do Inventário

Tabela:

inventory_items

Campos:

inventory_id

product_id

system_quantity

counted_quantity

difference

adjusted
21. Tela Inventário

Layout:

--------------------------------

Inventário


Produto

Qtd Sistema

Qtd Contada

Diferença


Produto A

100

98

-2


Produto B

50

52

+2


[Finalizar]

--------------------------------
22. Custo Médio

O sistema deverá utilizar custo médio ponderado.

Fórmula:

Novo custo médio

=

(Estoque atual × custo atual
+
Entrada × novo custo)

/

Quantidade total

Exemplo:

Estoque:

10 unidades

Custo:

R$100


Entrada:

20 unidades

Custo:

R$120


Novo custo médio:

R$113,33
23. Histórico de Custos

Toda alteração deverá registrar:

Produto

Custo anterior

Novo custo

Origem

Usuário

Data
24. Alertas de Estoque

Criar alertas:

Estoque baixo

Produto sem estoque

Produto parado

Excesso estoque
25. Estoque Mínimo

Cada produto poderá possuir:

Quantidade mínima

Quantidade ideal

Exemplo:

Mínimo:

10 unidades


Ideal:

50 unidades
26. Estoque Máximo

Permitir controle:

Evitar excesso de investimento

Identificar produtos parados
27. Produtos Parados

Considerar:

Sem venda por determinado período

Configuração:

30 dias

60 dias

90 dias
28. Transferência Entre Estoques

Permitir:

Estoque A

↓

Estoque B

Registrar:

Origem

Destino

Produto

Quantidade

Usuário
29. Relatórios de Estoque

Criar:

Estoque atual

Valor estoque

Movimentações

Produtos sem estoque

Produtos abaixo mínimo

Inventário

Produtos parados
30. Dashboard Estoque

Exibir:

Total produtos

Quantidade total

Valor investido

Produtos críticos

Últimas movimentações
31. Valor Financeiro do Estoque

Cálculo:

Valor estoque

=

Quantidade disponível

×

Custo médio

Exemplo:

100 produtos

Custo médio R$50


Estoque:

R$5.000
32. Integração com Vendas

Ao vender:

Venda aprovada

↓

Baixa estoque

↓

Atualiza custo

↓

Registra movimentação
33. Integração com Financeiro

O estoque deverá alimentar:

Valor investido

Custo mercadorias vendidas

Lucro real
34. Integração com Produtos

Produto deverá mostrar:

Quantidade disponível

Última entrada

Última saída

Valor estoque
35. Controle de Permissões

Permissões:

stock.view

stock.create

stock.adjust

stock.inventory

stock.transfer
36. Serviços Backend

Criar:

StockService

InventoryService

StockMovementService

CostAverageService

StockReportService
37. Controllers

Criar:

StockController

InventoryController

MovementController
38. API Estoque

Endpoints:

GET /api/v1/stock

GET /api/v1/stock/{product}

POST /api/v1/stock/movement

POST /api/v1/inventory

PUT /api/v1/inventory/{id}/finish
39. Auditoria

Registrar:

Entrada

Saída

Ajuste

Alteração custo

Inventário realizado
40. Regras de Negócio
Regra 1

Toda movimentação deve possuir origem.

Regra 2

Nenhuma movimentação pode ser apagada.

Regra 3

Correções devem gerar nova movimentação.

Regra 4

Produtos vendidos não podem ser removidos.

41. Critérios de Aceitação
[ ] Controle estoque funcionando

[ ] Entrada automática importação

[ ] Baixa automática venda

[ ] Ajustes funcionando

[ ] Inventário funcionando

[ ] Custo médio calculado

[ ] Alertas funcionando

[ ] Relatórios funcionando

[ ] Auditoria aplicada

[ ] Permissões aplicadas
Encerramento da Parte 53

O módulo de estoque será responsável por transformar o ImportControl em uma ferramenta de gestão completa.

O proprietário terá visão:

Quanto tenho?

Onde está?

Quanto investi?

Quanto vale meu estoque?

Quais produtos precisam de atenção?

A arquitetura permitirá crescimento futuro para:

Múltiplos depósitos

Código de barras

Leitores externos

Integração marketplaces

Controle avançado de logística