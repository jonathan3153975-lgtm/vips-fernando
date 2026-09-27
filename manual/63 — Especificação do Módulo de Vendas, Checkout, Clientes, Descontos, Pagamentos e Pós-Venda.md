# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 63 — Especificação do Módulo de Vendas, Checkout, Clientes, Descontos, Pagamentos e Pós-Venda

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Processo completo de vendas, atendimento ao cliente, checkout, pagamentos, regras comerciais e relacionamento pós-venda

---

# 1. Objetivo

O módulo de Vendas será responsável por controlar todo o processo comercial do negócio, desde o atendimento ao cliente até a finalização financeira.

O objetivo é permitir:


Registrar vendas rapidamente

Controlar clientes

Aplicar descontos com segurança

Alterar preços no momento da venda

Atualizar estoque automaticamente

Controlar pagamentos

Calcular lucro real

Criar histórico comercial


---

# 2. Conceito Geral

O fluxo comercial será:


Cliente

↓

Seleção produtos

↓

Carrinho de venda

↓

Aplicação descontos

↓

Definição pagamento

↓

Finalização

↓

Baixa estoque

↓

Movimento financeiro

↓

Atualização cliente

↓

Relatórios


---

# 3. Requisitos Funcionais

O módulo deverá permitir:


RF001 - Criar venda

RF002 - Adicionar produtos

RF003 - Alterar quantidade

RF004 - Alterar preço autorizado

RF005 - Aplicar desconto

RF006 - Registrar pagamento

RF007 - Cancelar venda

RF008 - Devolver produtos

RF009 - Atualizar estoque

RF010 - Gerar histórico cliente


---

# 4. Conceito de Venda

Uma venda representa uma transação comercial concluída.

Exemplo:


Cliente:

João Silva

Produtos:

iPhone 15

AirPods

Pagamento:

PIX

Total:

R$6.500


---

# 5. Cadastro de Clientes

O vendedor deverá poder:


Cadastrar cliente

Pesquisar cliente existente

Atualizar dados

Consultar histórico


---

# 6. Informações do Cliente

Campos:


Nome

CPF/CNPJ

Telefone

WhatsApp

Email

Endereço

Observações

Data nascimento

Tags


---

# 7. Tabela Customers

```sql
CREATE TABLE customers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

name VARCHAR(150),

document VARCHAR(30),

phone VARCHAR(30),

whatsapp VARCHAR(30),

email VARCHAR(150),

address TEXT,

notes TEXT,

status VARCHAR(30),

created_at TIMESTAMP,

updated_at TIMESTAMP

);
8. Classificação de Clientes

Criar categorias:

Novo cliente

Cliente ativo

Cliente VIP

Cliente inativo

Cliente potencial
9. Tags de Clientes

Permitir:

Exemplo:

Cliente VIP

Compra eletrônicos

Alto poder aquisitivo

Revendedor

Tabela:

customer_tags
10. Histórico do Cliente

Registrar:

Compras realizadas

Produtos adquiridos

Valor total gasto

Última compra

Preferências
11. Tela de Venda

Interface deverá ser otimizada para velocidade.

Elementos:

Busca produto

Leitor código barras

Carrinho

Cliente

Pagamento

Resumo
12. Pesquisa de Produto

Permitir:

Nome

SKU

Código barras

Categoria

Marca
13. Carrinho de Venda

Cada item deverá possuir:

Produto

Quantidade

Preço unitário

Desconto

Subtotal

Custo

Lucro
14. Tabela Sales
CREATE TABLE sales (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT,

customer_id BIGINT,

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
15. Numeração da Venda

Gerar automaticamente:

Exemplo:

VEN-2026-000001
16. Status da Venda

Estados:

OPEN

Aberta


COMPLETED

Finalizada


CANCELLED

Cancelada


RETURNED

Devolvida
17. Itens da Venda

Tabela:

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
18. Cálculo Automático

Sistema deverá calcular:

Subtotal

-

Desconto

=

Total venda

Lucro:

Venda

-

Custo produto

=

Lucro
19. Alteração de Preço no Momento da Venda

Permitir:

Preço padrão

Preço promocional

Preço personalizado

Regra:

Usuário comum:

Pode usar preço padrão

Administrador:

Pode alterar livremente
20. Controle de Desconto

Tipos:

Percentual

Valor fixo

Campanha

Autorizado manualmente
21. Regra de Desconto

Exemplo:

Produto:

R$2.000

Desconto máximo:

10%

Usuário tenta:

20%

Sistema:

Solicita autorização administrador
22. Tabela Sale Discounts
CREATE TABLE sale_discounts (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

type VARCHAR(30),

value DECIMAL(12,2),

user_id BIGINT,

reason TEXT,

created_at TIMESTAMP

);
23. Formas de Pagamento

Permitir:

Dinheiro

PIX

Cartão débito

Cartão crédito

Transferência

Parcelado

Outro
24. Tabela Payments
CREATE TABLE payments (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

method VARCHAR(50),

amount DECIMAL(12,2),

installments INT,

status VARCHAR(30),

payment_date DATETIME

);
25. Venda Parcelada

Controlar:

Quantidade parcelas

Valor parcela

Vencimento

Status pagamento
26. Contas a Receber

Venda parcelada deverá gerar:

Conta pendente

Parcelas

Vencimentos

Baixas
27. Finalização da Venda

Ao concluir:

Sistema executa:

Validar estoque

Registrar venda

Baixar estoque

Registrar financeiro

Calcular lucro

Atualizar cliente

Gerar auditoria
28. Baixa de Estoque

Gerar:

Movimento tipo SALE

Exemplo:

iPhone

Entrada:

10 unidades


Venda:

1 unidade


Saldo:

9 unidades
29. Cancelamento de Venda

Ao cancelar:

Reverter estoque

Cancelar financeiro

Registrar motivo

Manter histórico
30. Devoluções

Permitir:

Devolver produto parcial

Devolver venda completa

Gerar crédito cliente
31. Tabela Sale Returns
CREATE TABLE sale_returns (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

sale_id BIGINT,

customer_id BIGINT,

reason TEXT,

amount DECIMAL(12,2),

status VARCHAR(30),

created_at TIMESTAMP

);
32. Pós-Venda

Criar recursos:

Histórico cliente

Contato futuro

Observações

Lembretes

Relacionamento
33. Follow-up Cliente

Exemplos:

Cliente comprou notebook

Enviar mensagem após 30 dias


Cliente VIP

Contato personalizado
34. Comissão de Vendedores

Preparar:

Percentual comissão

Valor comissão

Vendedor responsável

Tabela:

sales_commissions
35. Dashboard Comercial

Exibir:

Vendas hoje

Vendas mês

Ticket médio

Produtos vendidos

Lucro

Melhores clientes

Melhores vendedores
36. Ranking de Vendedores

Critérios:

Maior faturamento

Maior lucro

Quantidade vendas
37. Relatórios

Criar:

Relatório vendas período

Produtos vendidos

Lucro vendas

Clientes compradores

Descontos aplicados

Vendas canceladas
38. Serviços Backend

Criar:

SaleService

CheckoutService

PaymentService

CustomerService

ReturnService

CommissionService
39. Controllers

Criar:

SaleController

CheckoutController

CustomerController

PaymentController

ReturnController
40. API Vendas

Endpoints:

GET /api/v1/sales

POST /api/v1/sales

GET /api/v1/sales/{id}

POST /api/v1/sales/{id}/complete

POST /api/v1/sales/{id}/cancel

POST /api/v1/sales/{id}/return
41. Permissões

Criar:

sales.view

sales.create

sales.edit

sales.discount

sales.change_price

sales.cancel

sales.return
42. Auditoria

Registrar:

Venda criada

Preço alterado

Desconto aplicado

Venda cancelada

Pagamento alterado

Devolução criada
43. Regras de Negócio
Regra 1

Venda nunca pode ser finalizada sem estoque disponível.

Regra 2

Toda venda deve possuir usuário responsável.

Regra 3

Venda cancelada nunca é apagada.

Regra 4

Descontos acima do limite exigem autorização.

Regra 5

Alterações comerciais devem gerar log.

44. Critérios de Aceitação
[ ] Cadastro cliente funcionando

[ ] Carrinho funcionando

[ ] Venda funcionando

[ ] Descontos funcionando

[ ] Alteração preço funcionando

[ ] Pagamentos funcionando

[ ] Estoque integrado

[ ] Financeiro integrado

[ ] Cancelamentos funcionando

[ ] Devoluções funcionando

[ ] Relatórios funcionando

[ ] Auditoria funcionando
Encerramento da Parte 63

O módulo de Vendas será o principal ponto de operação diária do ImportControl.

Ele deverá ser rápido, simples e seguro, permitindo que o vendedor realize uma venda em poucos passos, enquanto o sistema controla automaticamente:

Estoque

Financeiro

Lucro

Cliente

Histórico

Indicadores

Este módulo transforma o sistema em uma verdadeira plataforma de gestão comercial.