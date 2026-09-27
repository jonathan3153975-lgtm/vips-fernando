# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 55 — Especificação do Módulo de Vendas, Carrinho, Checkout e Processo Comercial

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Processo completo de venda, carrinho, precificação, descontos, pagamentos, estoque e integração financeira

---

# 1. Objetivo

O módulo de Vendas será responsável por controlar todo o processo comercial, desde a seleção dos produtos até a conclusão da venda.

O objetivo é permitir que o usuário realize vendas de forma rápida, segura e com controle total sobre:


Produtos vendidos

Valores praticados

Descontos concedidos

Formas de pagamento

Lucro obtido

Baixa de estoque

Histórico comercial


---

# 2. Conceito Geral

O fluxo comercial será:


Cliente selecionado

    ↓

Produtos adicionados

    ↓

Validação estoque

    ↓

Definição preços

    ↓

Aplicação descontos

    ↓

Pagamento

    ↓

Finalização venda

    ↓

Baixa estoque

    ↓

Registro financeiro

    ↓

Atualização histórico cliente


---

# 3. Requisitos Funcionais

O módulo deverá permitir:


RF001 - Criar vendas

RF002 - Adicionar produtos

RF003 - Alterar quantidade

RF004 - Alterar preço autorizado

RF005 - Aplicar desconto

RF006 - Registrar pagamentos

RF007 - Finalizar venda

RF008 - Cancelar venda

RF009 - Emitir comprovante

RF010 - Calcular lucro


---

# 4. Tipos de Venda

Preparar suporte para:


Venda balcão

Venda direta

Venda para cliente cadastrado

Venda futura

Venda parcelada

Venda com reserva


---

# 5. Tela Principal de Vendas

Layout:


Nova Venda

Cliente:

[Selecionar cliente]

Produto:

[Buscar produto]

Carrinho:

Produto | Qtd | Preço | Total

Subtotal:

R$

Desconto:

R$

Total:

R$

Pagamento:

[Selecionar]

[Finalizar Venda]


---

# 6. Criação da Venda

Ao iniciar:

Sistema cria:


Número da venda

Usuário responsável

Data/hora

Cliente opcional

Status inicial


---

# 7. Número da Venda

Formato:


VEN-2026-000001

VEN-2026-000002


---

Composição:


VEN

Ano

Sequencial


---

# 8. Status da Venda

Estados:


OPEN

Venda iniciada

PENDING_PAYMENT

Aguardando pagamento

PAID

Pagamento confirmado

COMPLETED

Finalizada

CANCELLED

Cancelada

RETURNED

Devolvida


---

# 9. Tabela Sales

```sql
CREATE TABLE sales (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

customer_id BIGINT NULL,

user_id BIGINT,

sale_number VARCHAR(50),

status VARCHAR(30),

subtotal DECIMAL(12,2),

discount DECIMAL(12,2),

total DECIMAL(12,2),

cost_total DECIMAL(12,2),

profit DECIMAL(12,2),

sale_date DATETIME,

created_at TIMESTAMP

);
10. Carrinho de Venda

O carrinho deverá controlar temporariamente:

Produtos selecionados

Quantidade

Preço aplicado

Desconto

Subtotal
11. Tabela Sale Items
CREATE TABLE sale_items (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

product_id BIGINT,

quantity DECIMAL(12,3),

cost_price DECIMAL(12,2),

sale_price DECIMAL(12,2),

discount DECIMAL(12,2),

subtotal DECIMAL(12,2)

);
12. Adicionar Produto

Processo:

Usuário pesquisa produto

↓

Seleciona item

↓

Sistema verifica estoque

↓

Adiciona ao carrinho

↓

Calcula valores
13. Busca de Produtos

Permitir:

Nome

SKU

Código barras

Categoria

Marca
14. Validação de Estoque

Ao adicionar produto:

Sistema verifica:

Quantidade disponível

Produto ativo

Local estoque

Caso insuficiente:

Produto sem estoque disponível
15. Alteração de Quantidade

Permitir:

Aumentar quantidade

Diminuir quantidade

Remover item

Sempre validar:

Saldo estoque
16. Alteração de Preço na Venda

O usuário poderá alterar preço quando possuir permissão.

Exemplo:

Produto:

Preço padrão:

R$500

Usuário altera:

Venda:

R$450

Sistema registra:

Preço original

Preço vendido

Usuário

Motivo
17. Tabela Price Changes
CREATE TABLE sale_price_changes (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

product_id BIGINT,

original_price DECIMAL(12,2),

new_price DECIMAL(12,2),

reason TEXT,

user_id BIGINT,

created_at TIMESTAMP

);
18. Controle de Preço Mínimo

Regra:

Preço vendido >= preço mínimo

Caso contrário:

Solicitar autorização

Fluxo:

Usuário tenta reduzir

↓

Sistema bloqueia

↓

Administrador autoriza

↓

Venda continua
19. Descontos

Tipos:

Desconto percentual

Desconto valor fixo

Desconto por item

Desconto total venda
20. Controle de Desconto por Perfil

Exemplo:

Usuário comum

Até 10%


Administrador

Sem limite
21. Motivo de Desconto

Obrigatório quando:

Desconto acima do limite

Preço abaixo do padrão

Venda especial
22. Cálculos da Venda
Subtotal
Quantidade × preço
Total
Subtotal - descontos
Lucro
Total venda - custo produtos
23. Cálculo do Lucro Real

Considerar:

Preço venda

Custo médio produto

Descontos

Custos adicionais
24. Exemplo

Produto:

Custo:

R$300


Venda:

R$500


Desconto:

R$50

Resultado:

Venda final:

R$450


Lucro:

R$150
25. Formas de Pagamento

Permitir:

Dinheiro

PIX

Cartão débito

Cartão crédito

Transferência

Fiado

Outros
26. Tabela Payments
CREATE TABLE payments (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

method VARCHAR(50),

amount DECIMAL(12,2),

installments INT,

status VARCHAR(30),

payment_date DATETIME

);
27. Pagamento Parcelado

Permitir:

Quantidade parcelas

Valor parcela

Datas vencimento

Status pagamento
28. Venda Fiado

Preparar:

Cliente possui crédito

Venda registrada

Conta a receber criada
29. Finalização da Venda

Fluxo:

Confirmar pagamento

↓

Atualizar status

↓

Baixar estoque

↓

Gerar financeiro

↓

Atualizar cliente

↓

Registrar auditoria
30. Baixa Estoque

Criar movimentação:

Tipo:

SAÍDA

Origem:

VENDA
31. Integração Financeira

Gerar:

Entrada financeira

Receita

Conta a receber

Fluxo caixa
32. Cancelamento de Venda

Regras:

Venda concluída não pode ser apagada

Processo:

Cancelar

↓

Registrar motivo

↓

Estornar estoque

↓

Estornar financeiro

↓

Auditar
33. Devolução de Venda

Preparar:

Produto devolvido

Entrada estoque

Crédito cliente

Reembolso
34. Comprovante de Venda

Gerar:

Número venda

Cliente

Produtos

Valores

Pagamento

Data

Responsável

Formatos:

PDF

Impressão térmica futura
35. Histórico de Vendas

Consultar:

Todas vendas

Por cliente

Por produto

Por período

Por vendedor
36. Dashboard Comercial

Mostrar:

Vendas hoje

Faturamento

Lucro

Ticket médio

Produtos vendidos

Clientes atendidos
37. Relatórios de Venda

Criar:

Vendas por período

Vendas por usuário

Produtos mais vendidos

Maior lucro

Descontos concedidos

Formas pagamento
38. Integração com Clientes

Atualizar:

Quantidade compras

Última compra

Valor total

Ranking cliente
39. Integração com Produtos

Atualizar:

Quantidade vendida

Última venda

Rotatividade

Ranking produtos
40. Integração com Estoque

Atualizar:

Saldo

Movimentações

Custo médio
41. Serviços Backend

Criar:

SaleService

CartService

PaymentService

DiscountService

SaleCalculationService

SaleReportService
42. Controllers

Criar:

SaleController

CheckoutController

PaymentController

SaleReportController
43. API Vendas

Endpoints:

GET    /api/v1/sales

POST   /api/v1/sales

PUT    /api/v1/sales/{id}

POST   /api/v1/sales/{id}/items

POST   /api/v1/sales/{id}/checkout

POST   /api/v1/sales/{id}/cancel
44. Permissões

Criar:

sales.view

sales.create

sales.edit

sales.cancel

sales.discount

sales.change_price

sales.export
45. Auditoria

Registrar:

Venda criada

Produto adicionado

Preço alterado

Desconto aplicado

Venda cancelada

Pagamento alterado
46. Regras de Negócio
Regra 1

Venda concluída nunca deve ser excluída.

Regra 2

Toda venda deve possuir responsável.

Regra 3

Alterações financeiras devem gerar histórico.

Regra 4

Baixa estoque ocorre somente após confirmação.

Regra 5

Lucro deve considerar custo real.

47. Critérios de Aceitação
[ ] Criar venda funcionando

[ ] Carrinho funcionando

[ ] Controle estoque integrado

[ ] Alteração preço controlada

[ ] Desconto com permissão

[ ] Pagamentos funcionando

[ ] Parcelamento preparado

[ ] Lucro calculado

[ ] Financeiro integrado

[ ] Cliente atualizado

[ ] Auditoria funcionando
Encerramento da Parte 55

O módulo de Vendas será o principal ponto operacional do ImportControl.

Ele reunirá:

Produtos

Clientes

Estoque

Financeiro

Lucro

Histórico comercial

permitindo que o proprietário tenha controle completo da operação diária.

A arquitetura estará preparada para evoluir futuramente para:

PDV completo

Emissão fiscal

Integração máquinas cartão

Marketplace

Aplicativo vendedor