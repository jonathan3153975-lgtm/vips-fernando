# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 33 — Especificação do Módulo de Estoque e Movimentação de Mercadorias

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Controle de estoque, movimentações, inventário, rastreabilidade e integração operacional

---

# 1. Objetivo

O módulo de Estoque será responsável por controlar toda movimentação física e financeira das mercadorias.

Ele deverá garantir que o proprietário saiba:


Quanto possui em estoque

Onde está cada produto

Quanto investiu

Quanto já vendeu

Quanto ainda pode vender

Quais produtos precisam reposição


---

# 2. Conceito do Estoque

O estoque representa a quantidade física disponível de cada produto.

Fluxo principal:


Compra internacional

↓

Entrada no estoque

↓

Armazenamento

↓

Venda

↓

Baixa automática

↓

Histórico de movimentação


---

# 3. Objetivos do Módulo

Permitir:


Controle de quantidade

Entrada automática

Saída por venda

Ajustes manuais

Inventário

Histórico completo

Alertas

Análise de giro


---

# 4. Menu Estoque

Estrutura:


Estoque

├── Visão geral

├── Produtos em estoque

├── Movimentações

├── Entrada manual

├── Ajustes

├── Inventário

├── Alertas

└── Relatórios


---

# 5. Conceito de Saldo de Estoque

O saldo será calculado:


Estoque atual

=

Entradas

Saídas

Ajustes


---

Exemplo:


Entrada:

100 unidades

Venda:

30 unidades

Ajuste:

+5 unidades

Saldo:

75 unidades


---

# 6. Cadastro de Estoque

Cada produto possuirá:


Quantidade atual

Quantidade mínima

Quantidade máxima

Localização

Última entrada

Última saída


---

# 7. Tabela Principal

## stock

Campos:

```sql
id

tenant_id

product_id

quantity

minimum_quantity

maximum_quantity

reserved_quantity

updated_at
8. Estoque Reservado

Preparar para vendas futuras.

Exemplo:

Produto:

Notebook


Estoque:

10


Reservado:

2


Disponível:

8

Campo:

reserved_quantity
9. Tipos de Movimentação

O sistema deverá controlar:

ENTRADA_COMPRA

SAIDA_VENDA

AJUSTE_POSITIVO

AJUSTE_NEGATIVO

DEVOLUCAO

PERDA

TRANSFERENCIA
10. Histórico de Movimentações

Toda alteração deverá gerar registro.

Tabela:

stock_movements
id

tenant_id

product_id

type

quantity

previous_quantity

new_quantity

reference_type

reference_id

user_id

description

created_at
11. Exemplo de Histórico

Produto:

iPhone 15

Movimentações:

01/02

Compra internacional

+20


10/02

Venda

-3


15/02

Ajuste inventário

-1
12. Entrada de Estoque

Principais origens:

Compra internacional

Compra nacional

Devolução

Ajuste manual
13. Entrada Automática por Compra

Fluxo:

Compra finalizada

↓

Sistema verifica produtos

↓

Atualiza estoque

↓

Cria movimentação

↓

Atualiza custo
14. Entrada Manual

Permitir:

Produto

Quantidade

Valor custo

Motivo

Documento

Exemplo:

Produto:

Mouse


Quantidade:

50


Motivo:

Compra local
15. Saída de Estoque

Principais origens:

Venda

Perda

Uso interno

Ajuste
16. Baixa Automática por Venda

Fluxo:

Venda concluída

↓

Sistema valida estoque

↓

Remove quantidade

↓

Registra movimentação

↓

Atualiza saldo
17. Bloqueio de Venda sem Estoque

Configuração:

Permitir venda negativa?

Sim

Não

Padrão recomendado:

Não permitir
18. Ajustes de Estoque

Utilizado para:

Diferença física

Erro cadastro

Produto perdido

Correção

Campos:

Produto

Quantidade atual

Quantidade correta

Diferença

Motivo
19. Inventário

Objetivo:

Comparar:

Estoque sistema

×

Estoque físico
20. Processo de Inventário

Fluxo:

Criar inventário

↓

Selecionar produtos

↓

Contagem física

↓

Informar quantidade real

↓

Sistema calcula diferença

↓

Confirmar ajuste
21. Tabela Inventário
inventories
id

tenant_id

name

status

started_at

finished_at

created_by
22. Itens Inventário
inventory_items
id

inventory_id

product_id

system_quantity

counted_quantity

difference

status
23. Status do Inventário
OPEN

COUNTING

PROCESSING

COMPLETED

CANCELLED
24. Alertas de Estoque

O sistema deverá alertar:

Estoque baixo

Produto zerado

Produto parado

Excesso estoque
25. Estoque Mínimo

Exemplo:

Produto:

Cabo USB

Configuração:

Estoque mínimo:

20

Quando:

Quantidade <= 20

Gerar alerta.

26. Dashboard de Estoque

Exibir:

Produtos cadastrados

Valor total estoque

Produtos baixo estoque

Produtos sem estoque

Últimas movimentações
27. Valor Financeiro do Estoque

Calcular:

Quantidade

×

Custo médio

Exemplo:

100 produtos

Custo médio:

R$500


Valor estoque:

R$50.000
28. Custo Médio

Implementar método:

Custo médio ponderado

Fórmula:

(Custo estoque atual + custo nova entrada)

/

Quantidade total

Exemplo:

Primeira compra:

10 unidades

R$100 cada

Nova compra:

20 unidades

R$120 cada

Novo custo médio:

R$113,33
29. Produtos Parados

Identificar:

Produtos sem venda há X dias

Configuração:

30 dias

60 dias

90 dias
30. Giro de Estoque

Indicador:

Quantidade vendida

/

Estoque médio

Mostrar:

Alto giro

Normal

Baixo giro
31. Relatórios

Criar:

Relatório de Estoque Atual

Mostrar:

Produto

Quantidade

Custo

Valor total
Relatório de Movimentações

Filtros:

Período

Produto

Tipo movimentação

Usuário
Relatório de Produtos Parados

Mostrar:

Produto

Última venda

Dias parado

Valor investido
32. Tela Visão Geral

Layout:

Estoque


[ Valor total ]

[ Produtos ]

[ Baixo estoque ]

[ Sem estoque ]


Tabela movimentações recentes
33. Tela Produtos em Estoque

Tabela:

Imagem

Produto

Quantidade

Custo médio

Valor estoque

Status

Ações
34. Tela Movimentações

Tabela:

Data

Produto

Tipo

Quantidade

Saldo anterior

Saldo atual

Usuário
35. Pesquisa de Estoque

Filtros:

Nome produto

Código

Categoria

Fornecedor

Status
36. Integração com Compras

Compra concluída:

+

Estoque

Atualizar:

Quantidade

Custo

Histórico
37. Integração com Vendas

Venda concluída:

-

Estoque

Atualizar:

Quantidade

Lucro

Giro
38. Integração com Financeiro

Estoque representa:

Capital investido parado

Permitir cálculo:

Valor investido em mercadorias
39. Serviços Backend

Criar:

StockService

MovementService

InventoryService

StockAlertService

AverageCostService
40. Controllers

Criar:

StockController

MovementController

InventoryController

StockReportController
41. Regras de Segurança

Controlar:

Quem visualiza estoque

Quem ajusta quantidade

Quem realiza inventário

Quem altera custos

Exemplo:

Vendedor:

Visualiza quantidade

Não ajusta

Administrador:

Controle total
42. Auditoria

Registrar:

Alteração estoque

Ajuste manual

Cancelamento venda

Inventário realizado
43. Funcionalidades Futuras

Preparar:

Código de barras

Leitor QR Code

Aplicativo móvel

Múltiplos depósitos

Transferência entre lojas

Integração marketplace
44. Critérios de Aceitação
[ ] Estoque atualizado automaticamente

[ ] Entrada por compra funcionando

[ ] Saída por venda funcionando

[ ] Histórico completo

[ ] Inventário funcionando

[ ] Alertas configurados

[ ] Custo médio calculado

[ ] Relatórios disponíveis

[ ] Auditoria registrada
Encerramento da Parte 33

O módulo de Estoque será responsável por garantir que o proprietário tenha controle completo das mercadorias.

A operação ficará organizada:

Compra internacional

↓

Entrada correta

↓

Controle físico

↓

Venda

↓

Lucro real

Este módulo será essencial para evitar perdas financeiras e melhorar a tomada de decisão.