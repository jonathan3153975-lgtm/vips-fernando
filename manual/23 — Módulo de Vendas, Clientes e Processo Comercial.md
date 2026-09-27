# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 23 — Módulo de Vendas, Clientes e Processo Comercial

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação funcional do módulo comercial, vendas, clientes e relacionamento

---

# 1. Objetivo

O módulo de Vendas será responsável por controlar todo o processo comercial do negócio, desde o cadastro do cliente até a finalização da venda.

O objetivo é permitir:

- cadastro de clientes;
- criação de pedidos;
- negociação de valores;
- aplicação de descontos;
- controle de pagamentos;
- baixa automática de estoque;
- cálculo de lucro;
- histórico de vendas.

---

# 2. Conceito do Processo Comercial

Fluxo principal:


Cliente cadastrado

↓

Nova venda

↓

Adicionar produtos

↓

Definir valores

↓

Aplicar desconto

↓

Escolher pagamento

↓

Finalizar venda

↓

Baixar estoque

↓

Registrar financeiro

↓

Gerar comprovante


---

# 3. Objetivo Gerencial

O sistema deverá responder:

- Quanto vendeu?
- Para quem vendeu?
- Qual produto vende mais?
- Quanto lucro gerou?
- Quais clientes compram mais?
- Quais vendas estão pendentes?

---

# 4. Menu Comercial

Estrutura:


Vendas

├── Nova venda

├── Vendas realizadas

├── Clientes

├── Orçamentos

├── Pagamentos

└── Relatórios comerciais


---

# 5. Tela Principal de Vendas

Layout:


Vendas

[ Nova Venda ]

Número

Cliente

Data

Valor

Pagamento

Status

Lucro

Ações


---

# 6. Cadastro de Clientes

O sistema deverá permitir cadastrar:


Pessoa Física

Pessoa Jurídica


---

# 7. Dados do Cliente

Campos:


Nome

CPF/CNPJ

Telefone

WhatsApp

Email

Endereço

Cidade

Estado

Observações


---

# 8. Cliente Pessoa Física

Exemplo:


Nome:

João Silva

CPF:

000.000.000-00

Telefone:

(51)99999-9999


---

# 9. Cliente Pessoa Jurídica

Campos adicionais:


Razão social

Nome fantasia

Inscrição estadual

Responsável


---

# 10. Histórico do Cliente

Cada cliente deverá possuir:


Compras realizadas

Valor total comprado

Última compra

Produtos adquiridos

Pendências financeiras


---

Exemplo:


Cliente:

Carlos

Compras:

15

Total:

R$32.500

Última compra:

01/08/2026


---

# 11. Cadastro Simplificado no Momento da Venda

Durante uma venda poderá ocorrer:


Cliente novo


---

Fluxo:


Nova venda

↓

Cadastrar cliente rápido

↓

Continuar venda


---

# 12. Criação de Venda

Campos principais:


Cliente

Data

Vendedor

Observação

Produtos

Pagamento


---

# 13. Número da Venda

Gerado automaticamente:

Exemplo:


VEN-2026-000001


---

Formato configurável.

---

# 14. Adicionar Produtos

Busca por:


Nome

SKU

Código barras

Categoria


---

Exemplo:


Produto:

AirPods Pro

Quantidade:

2

Preço:

R$1.800


---

# 15. Itens da Venda

Cada item deverá armazenar:


Produto

Quantidade

Preço unitário

Desconto

Preço final

Custo

Lucro


---

# 16. Alteração de Preço no Momento da Venda

Regra fundamental:

O vendedor poderá negociar o preço.

Exemplo:

Produto:


Notebook Dell


---

Preço padrão:


R$5.500


---

Venda negociada:


R$5.200


---

Sistema registra:


Preço original

Preço vendido

Diferença

Usuário responsável


---

# 17. Descontos

Permitir:

## Desconto percentual

Exemplo:


10%


---

## Desconto em valor

Exemplo:


R$200


---

# 18. Limite de Desconto

Configuração:


Desconto máximo permitido


---

Exemplo:

Usuário:


Máximo 15%


---

Tentativa:


25%


---

Sistema:


Solicitar autorização


---

# 19. Autorização de Desconto

Fluxo:


Usuário solicita

↓

Administrador recebe

↓

Aprova/Rejeita

↓

Venda continua


---

Registrar:


Usuário aprovador

Data

Motivo


---

# 20. Cálculo de Lucro da Venda

Fórmula:


Lucro =
Valor venda - custo real


---

Exemplo:

Produto:


Custo:

R$1.000


---

Venda:


R$1.500


---

Lucro:


R$500


---

# 21. Margem da Venda

Fórmula:


Lucro ÷ Venda × 100


---

Exemplo:


Venda:

R$10.000

Lucro:

R$3.000

Margem:

30%


---

# 22. Formas de Pagamento

Configurar:


Dinheiro

PIX

Cartão débito

Cartão crédito

Transferência

Parcelado

Outro


---

# 23. Venda Parcelada

Permitir:


Quantidade parcelas

Valor parcela

Vencimento


---

Exemplo:


Venda:

R$3.000

3x

R$1.000/mês


---

# 24. Controle de Recebimentos

Criar:


Contas a receber


---

Status:


PENDENTE

PAGO

ATRASADO

CANCELADO


---

# 25. Baixa de Pagamento

Ao receber:

Registrar:


Data pagamento

Valor

Forma pagamento

Usuário


---

# 26. Venda Fiada / Cliente em Aberto

Permitir:


Venda pendente


---

Exemplo:

Cliente:


Comprou hoje

Pagamento mês seguinte


---

Sistema controla:


Débito

Vencimento

Histórico


---

# 27. Status da Venda

Estados:


DRAFT

PENDING

CONFIRMED

PAID

CANCELED


---

## DRAFT

Rascunho.

---

## PENDING

Aguardando pagamento.

---

## CONFIRMED

Venda confirmada.

---

## PAID

Pagamento concluído.

---

# 28. Cancelamento de Venda

Ao cancelar:

Sistema deverá:


Estornar estoque

Cancelar financeiro

Registrar motivo

Manter histórico


---

# 29. Devolução de Produto

Permitir:


Devolução total

Devolução parcial


---

Fluxo:


Cliente devolve

↓

Registrar produto

↓

Retornar estoque

↓

Ajustar financeiro


---

# 30. Comprovante de Venda

Gerar:


PDF

Impressão

Envio digital


---

Informações:


Empresa

Cliente

Produtos

Valores

Pagamento

Data


---

# 31. Compartilhamento WhatsApp

Preparar:


Enviar comprovante

Enviar orçamento

Enviar cobrança


---

# 32. Orçamentos

Criar módulo:


Orçamentos


---

Fluxo:


Orçamento criado

↓

Cliente aceita

↓

Converter em venda


---

# 33. Status do Orçamento


CRIADO

ENVIADO

APROVADO

RECUSADO

EXPIRADO


---

# 34. Validade do Orçamento

Permitir:


Data validade


---

Exemplo:


Válido por 7 dias


---

# 35. Histórico Comercial

Registrar:


Alteração preço

Desconto aplicado

Cancelamento

Pagamento

Observações


---

# 36. Ranking de Clientes

Dashboard:

Mostrar:


Clientes que mais compram

Maior faturamento

Maior lucro gerado


---

# 37. Clientes Inativos

Identificar:


Sem compra há X dias


---

Exemplo:


Cliente:

Maria

Última compra:

180 dias atrás


---

# 38. Banco de Dados

## customers

```sql
id

tenant_id

type

name

document

phone

email

address

city

state

notes

created_at

deleted_at
sales
id

tenant_id

customer_id

seller_id

sale_number

status

subtotal

discount

total

profit

payment_status

created_at
sale_items
id

sale_id

product_id

quantity

cost_price

original_price

sale_price

discount

profit
payments
id

sale_id

method

amount

due_date

paid_at

status
sale_history
id

sale_id

action

old_data

new_data

user_id

created_at
39. Services Backend

Criar:

SaleService

CustomerService

PaymentService

DiscountService

ReceiptService

ProfitCalculationService
40. Controllers

Criar:

SaleController

CustomerController

PaymentController

BudgetController

ReceiptController
41. Regras de Negócio
Regra 1

Toda venda finalizada deve baixar estoque.

Regra 2

Toda venda deve guardar o custo no momento da venda.

Regra 3

Alteração futura de custo não altera vendas antigas.

Regra 4

Venda cancelada deve gerar estorno.

Regra 5

Todo desconto deve possuir rastreabilidade.

42. Auditoria

Registrar:

alteração de preço;
descontos;
cancelamentos;
pagamentos;
devoluções.
43. Critérios de Aceitação
[ ] Cadastro clientes

[ ] Nova venda

[ ] Produtos na venda

[ ] Alteração preço

[ ] Desconto

[ ] Pagamentos

[ ] Parcelamento

[ ] Baixa estoque

[ ] Cálculo lucro

[ ] Cancelamento

[ ] Devolução

[ ] Orçamento

[ ] Relatórios
Encerramento da Parte 23

O módulo de Vendas será responsável pela operação diária do vendedor.

Ele conecta todo o fluxo do ImportControl:

Importação

↓

Produto

↓

Estoque

↓

Venda

↓

Financeiro

↓

Lucro

A estrutura permite que o sistema acompanhe desde uma venda simples até uma operação comercial profissional.