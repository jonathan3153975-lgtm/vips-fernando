# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 42 — Especificação do Módulo de Estoque, Inventário e Controle de Movimentações

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Gestão de estoque, controle de entradas e saídas, inventário físico e rastreabilidade de produtos

---

# 1. Objetivo

O módulo de Estoque será responsável por controlar todo o ciclo de movimentação dos produtos dentro do sistema.

O objetivo é garantir que o proprietário tenha controle preciso sobre:


Quantidade disponível

Produtos vendidos

Produtos recebidos

Produtos reservados

Produtos perdidos

Produtos parados

Valor total investido em estoque


---

# 2. Conceito Geral

O estoque será alimentado por:


Compras internacionais

↓

Entrada estoque

↓

Armazenamento

↓

Venda

↓

Baixa automática

↓

Controle financeiro


---

# 3. Objetivos Específicos

O módulo deverá permitir:


Cadastrar estoque inicial

Registrar entradas

Registrar saídas

Controlar perdas

Realizar inventário

Identificar divergências

Monitorar produtos críticos

Rastrear todas movimentações


---

# 4. Menu Estoque

Estrutura:


Estoque

├── Visão geral

├── Produtos em estoque

├── Movimentações

├── Inventário

├── Ajustes

├── Transferências

├── Alertas

└── Histórico


---

# 5. Dashboard de Estoque

Exibir:


Valor total estoque

Quantidade total produtos

Produtos disponíveis

Produtos vendidos

Produtos abaixo mínimo

Produtos sem movimentação


---

Exemplo:


Estoque atual:

R$150.000

Produtos:

320 unidades

Baixo estoque:

12 produtos


---

# 6. Estrutura de Estoque

Cada produto deverá possuir:


Quantidade atual

Quantidade reservada

Quantidade disponível

Custo médio

Última entrada

Última saída


---

Fórmula:


Estoque disponível

=

Quantidade física

Quantidade reservada


---

# 7. Cadastro de Estoque

Permitir:


Entrada manual

Entrada por compra

Importação inicial

Ajuste positivo


---

# 8. Entrada de Produtos

Origem:


Compra internacional

Compra nacional

Ajuste

Devolução

Cadastro inicial


---

Exemplo:


Produto:

iPhone 15

Quantidade:

20 unidades

Custo:

R$4.500


---

# 9. Processo de Entrada Automática

Fluxo:


Compra confirmada

↓

Produtos recebidos

↓

Atualizar estoque

↓

Atualizar custo médio

↓

Criar movimentação


---

# 10. Saída de Produtos

Origem:


Venda

Perda

Dano

Uso interno

Ajuste negativo


---

# 11. Baixa Automática por Venda

Fluxo:


Venda aprovada

↓

Verificar estoque

↓

Baixar quantidade

↓

Registrar movimentação

↓

Atualizar financeiro


---

# 12. Reserva de Produtos

Permitir reservar produtos antes da venda finalizada.

Exemplo:


Cliente solicitou produto

Produto reservado

Aguardando pagamento


---

Cálculo:


Disponível

=

Estoque físico

Reservado


---

# 13. Controle de Estoque Negativo

Não permitir:


Venda maior que estoque disponível


---

Exceção:

Administrador poderá autorizar:


Venda futura

Pré-venda

Backorder


---

# 14. Movimentações de Estoque

Toda alteração deverá gerar histórico.

Registrar:


Data

Produto

Quantidade anterior

Quantidade movimentada

Quantidade atual

Tipo

Usuário

Origem


---

# 15. Tipos de Movimentação

Criar enum:


ENTRY

Entrada

SALE

Venda

RETURN

Devolução

LOSS

Perda

ADJUSTMENT_PLUS

Ajuste positivo

ADJUSTMENT_MINUS

Ajuste negativo

TRANSFER

Transferência


---

# 16. Tabela stock_movements

Estrutura:

```sql
CREATE TABLE stock_movements (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT NOT NULL,

product_id BIGINT NOT NULL,

type VARCHAR(50),

quantity INT,

previous_quantity INT,

new_quantity INT,

reference_type VARCHAR(50),

reference_id BIGINT,

user_id BIGINT,

description TEXT,

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
17. Estoque por Localização

Preparar suporte para múltiplos locais.

Exemplo:

Casa

Depósito

Loja física

Outro endereço

Tabela:

warehouses
id

tenant_id

name

address

status
18. Estoque por Local

Tabela:

warehouse_stock
id

warehouse_id

product_id

quantity

Permite:

Mesmo produto em vários locais
19. Transferência de Estoque

Permitir:

Local A

↓

Local B

Exemplo:

Depósito

10 unidades


Transferir


Loja

5 unidades
20. Inventário Físico

Objetivo:

Comparar:

Estoque sistema

versus

Estoque real
21. Processo de Inventário

Fluxo:

Criar inventário

↓

Contagem física

↓

Informar quantidade encontrada

↓

Comparar diferenças

↓

Gerar ajustes
22. Tabela Inventários
inventories
id

tenant_id

warehouse_id

date

status

responsible_user_id

created_at
23. Itens Inventário
inventory_items
id

inventory_id

product_id

system_quantity

counted_quantity

difference

status
24. Ajuste Automático

Após aprovação:

Diferença positiva

↓

Entrada estoque


Diferença negativa

↓

Saída estoque
25. Aprovação de Ajustes

Regra:

Ajustes grandes precisam aprovação.

Exemplo:

Diferença > 10 unidades

ou

Valor > R$1.000
26. Controle de Produtos Parados

Identificar:

Produto sem venda

Quantidade

Valor investido

Dias parado

Exemplo:

Notebook X

120 dias parado

R$12.000 estoque
27. Alertas de Estoque

Criar alertas:

Estoque abaixo mínimo

Produto zerado

Produto parado

Grande redução estoque

Divergência inventário
28. Estoque Mínimo

Cada produto possuirá:

Estoque mínimo

Estoque ideal

Estoque máximo

Exemplo:

Produto:

AirPods


Mínimo:

5


Ideal:

20
29. Custo Médio do Estoque

Utilizar cálculo:

Novo custo médio

=

(Estoque atual × custo atual)

+

(nova entrada × novo custo)

/

Quantidade total
30. Valorização do Estoque

Mostrar:

Quantidade

Custo unitário

Valor total

Exemplo:

10 iPhones

R$4.500 cada


Total:

R$45.000
31. Histórico do Produto

Tela:

Produto

├── Compras

├── Entradas

├── Vendas

├── Ajustes

├── Custos

└── Inventários
32. Integração com Compras

Quando:

Compra recebida

Sistema:

Aumenta estoque

Atualiza custo

Registra origem
33. Integração com Vendas

Quando:

Venda confirmada

Sistema:

Baixa estoque

Calcula lucro

Atualiza indicadores
34. Integração Financeira

Movimentações podem gerar:

Custos

Perdas

Ajustes financeiros
35. Permissões

Controle:

Vendedor

Pode:

Consultar estoque

Não pode:

Alterar quantidade

Excluir movimentação
Administrador

Pode:

Ajustar estoque

Realizar inventário

Visualizar custos
36. Auditoria

Registrar:

Alteração estoque

Ajuste manual

Exclusão

Inventário aprovado
37. Serviços Backend

Criar:

StockService

InventoryService

MovementService

WarehouseService

StockAlertService

CostAverageService
38. Controllers

Criar:

StockController

InventoryController

MovementController

WarehouseController

AdjustmentController
39. Relatórios do Estoque

Criar:

Estoque atual

Movimentações

Produtos parados

Inventário

Valor estoque

Curva ABC
40. Curva ABC

Classificar produtos:

Classe A
Poucos produtos

Maior valor financeiro
Classe B
Importância média
Classe C
Grande quantidade

Baixo impacto
41. Banco de Dados Complementar
warehouses
id

tenant_id

name

address

status
warehouse_stock
id

warehouse_id

product_id

quantity
inventories
id

tenant_id

warehouse_id

status

date
inventory_items
id

inventory_id

product_id

system_quantity

counted_quantity

difference
42. Critérios de Aceitação
[ ] Entrada de produtos funcionando

[ ] Saída por venda funcionando

[ ] Histórico completo

[ ] Controle de estoque negativo

[ ] Reserva de produtos

[ ] Inventário funcionando

[ ] Ajustes registrados

[ ] Alertas funcionando

[ ] Estoque por localização

[ ] Relatórios funcionando

[ ] Auditoria ativa
Encerramento da Parte 42

O módulo de Estoque será responsável por garantir a confiabilidade operacional do ImportControl.

Com ele o proprietário terá:

Controle real do patrimônio

Visibilidade dos produtos disponíveis

Redução de perdas

Melhor planejamento de compras

Histórico completo das operações

Este módulo será um dos pilares do sistema, pois conecta diretamente:

Compras internacionais

Produtos

Vendas

Financeiro

Relatórios