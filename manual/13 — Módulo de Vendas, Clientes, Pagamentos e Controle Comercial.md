# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 13 — Módulo de Vendas, Clientes, Pagamentos e Controle Comercial

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação funcional e técnica do módulo comercial

---

# 1. Objetivo

O módulo de Vendas será responsável por controlar todo o processo comercial do negócio, desde o cadastro do cliente até a finalização da venda.

O objetivo é permitir:

- registrar vendas rapidamente;
- controlar clientes;
- controlar pagamentos;
- atualizar estoque automaticamente;
- calcular lucro real;
- acompanhar histórico comercial;
- gerar indicadores de desempenho.

---

# 2. Conceito Geral

Fluxo comercial:


Cliente interessado

↓

Seleção dos produtos

↓

Definição do preço

↓

Aplicação de desconto (se necessário)

↓

Pagamento

↓

Finalização

↓

Baixa estoque

↓

Registro financeiro

↓

Atualização dos indicadores


---

# 3. Estrutura do Módulo


Vendas

├── Clientes

├── Orçamentos

├── Pedidos

├── Venda rápida

├── Pagamentos

├── Parcelamentos

├── Devoluções

├── Comissões

├── Histórico

└── Relatórios


---

# 4. Cadastro de Clientes

O sistema deverá permitir cadastrar compradores.

Campos:


Nome

CPF/CNPJ

Telefone

Email

Endereço

Cidade

Estado

Observações

Data cadastro


---

Exemplo:


Nome:

João Silva

Telefone:

(51)99999-9999

Cidade:

Porto Alegre


---

# 5. Tipos de Cliente

Permitir classificação:


Pessoa Física

Pessoa Jurídica

Revendedor

Cliente VIP

Cliente eventual


---

# 6. Histórico do Cliente

A página do cliente deverá apresentar:


Dados pessoais

↓

Compras realizadas

↓

Produtos adquiridos

↓

Valores gastos

↓

Pagamentos pendentes

↓

Observações


---

# 7. Classificação de Clientes

Permitir criar categorias:

Exemplo:


VIP

Atacado

Varejo

Parceiro


---

Benefícios futuros:

- descontos automáticos;
- condições especiais;
- relatórios.

---

# 8. Venda Rápida

Como o negócio é baseado em venda direta, deverá existir uma tela otimizada.

Objetivo:

Realizar uma venda em poucos passos.

---

Fluxo:


Buscar produto

↓

Adicionar ao carrinho

↓

Definir preço

↓

Escolher pagamento

↓

Finalizar


---

# 9. Tela de Venda

Layout:


Nova Venda

Cliente:

[ Buscar cliente ]

Produto Qtd Valor

iPhone 1 R$5.000

Notebook 1 R$4.000

Subtotal:

R$9.000

Desconto:

R$500

Total:

R$8.500

[Finalizar Venda]


---

# 10. Carrinho de Venda

Cada item deverá possuir:


Produto

Quantidade

Preço tabela

Preço vendido

Desconto

Custo

Lucro


---

Exemplo:


Produto:

Celular

Preço tabela:

R$3.000

Venda:

R$2.800


---

# 11. Alteração de Preço no Momento da Venda

O vendedor poderá alterar o preço.

Porém o sistema deverá registrar:


Preço original

Preço final

Diferença

Usuário

Data

Motivo


---

Exemplo:


Preço original:

R$5.000

Preço vendido:

R$4.700

Motivo:

Cliente recorrente


---

# 12. Controle de Desconto

Permitir:

## Desconto percentual

Exemplo:


10%


---

## Desconto em valor

Exemplo:


R$200


---

# 13. Limite de Desconto

Configurar por perfil.

Exemplo:

Administrador:


Até 100%


---

Vendedor:


Até 10%


---

Acima do limite:

Solicitar aprovação.

---

# 14. Aprovação de Venda

Fluxo:


Vendedor aplica desconto acima do limite

↓

Venda fica pendente

↓

Administrador aprova

↓

Venda finalizada


---

# 15. Formas de Pagamento

Sistema deverá suportar:


Dinheiro

PIX

Cartão débito

Cartão crédito

Transferência

Parcelado

Outro


---

# 16. Pagamento Múltiplo

Permitir uma venda utilizando mais de uma forma.

Exemplo:

Venda:


R$5.000


Pagamento:


PIX:

R$3.000

Cartão:

R$2.000


---

# 17. Parcelamentos

Permitir:


Número parcelas

Valor parcela

Data vencimento

Status pagamento


---

Exemplo:


Venda:

R$6.000

6x

R$1.000


---

# 18. Contas a Receber

Quando venda for parcelada:

Gerar automaticamente:


Recebimento 1

Recebimento 2

Recebimento 3


---

Status:


Pendente

Pago

Atrasado

Cancelado


---

# 19. Finalização da Venda

Ao confirmar:

Sistema executa:


Criar venda

↓

Criar itens venda

↓

Baixar estoque

↓

Registrar pagamento

↓

Calcular lucro

↓

Criar lançamento financeiro

↓

Gerar comprovante


---

# 20. Número da Venda

Cada venda deverá possuir código único.

Exemplo:


VEN-2026-000001


---

# 21. Status da Venda

Estados:


DRAFT

PENDING

CONFIRMED

PAID

PARTIAL

CANCELED

RETURNED


---

## DRAFT

Venda em preparação.

---

## CONFIRMED

Venda realizada.

---

## PARTIAL

Pagamento parcial.

---

## CANCELED

Venda cancelada.

---

# 22. Cancelamento de Venda

Ao cancelar:

Sistema deverá:


Restaurar estoque

Cancelar financeiro

Registrar motivo

Criar auditoria


---

# 23. Devolução

Permitir:


Produto devolvido

Quantidade

Motivo

Data


---

Exemplo:


Defeito

Arrependimento

Troca


---

# 24. Troca de Produto

Fluxo:


Produto devolvido

↓

Entrada estoque

↓

Novo produto entregue

↓

Ajuste financeiro


---

# 25. Cálculo de Lucro

Cada venda deverá calcular:


Valor venda

Custo produto

Descontos

=

Lucro


---

Exemplo:

Venda:


R$1.000


Custo:


R$700


Lucro:


R$300


---

# 26. Comissão de Venda

Preparar estrutura para comissão.

Campos:


Vendedor

Percentual

Valor comissão

Status


---

Exemplo:


Venda:

R$10.000

Comissão:

5%

Valor:

R$500


---

# 27. Impressão de Comprovante

Gerar:

- PDF;
- impressão térmica futura.

---

Informações:


Empresa

Cliente

Produtos

Valores

Pagamento

Data

Vendedor


---

# 28. Relatórios Comerciais

## Vendas por período

Filtros:


Hoje

Semana

Mês

Personalizado


---

## Produtos mais vendidos

Mostrar:


Produto

Quantidade

Faturamento

Lucro


---

## Clientes que mais compram

Mostrar:


Cliente

Quantidade compras

Valor total


---

## Margem comercial

Mostrar:


Venda

Custo

Lucro

Margem %


---

# 29. Dashboard Comercial

Cards:


Vendas hoje

Faturamento mês

Lucro mês

Clientes novos

Contas pendentes


---

Gráficos:


Venda por período

Produtos vendidos

Formas pagamento

Margem


---

# 30. Banco de Dados

## sales

```sql
id

tenant_id

customer_id

user_id

sale_number

status

subtotal

discount

total

profit

created_at

updated_at
sale_items
id

sale_id

product_id

quantity

cost_price

sale_price

discount

profit
payments
id

sale_id

payment_method

amount

status

paid_at
customers
id

tenant_id

name

document

phone

email

address

created_at
31. Serviços Backend

Criar:

SaleService

CustomerService

PaymentService

ProfitService

CommissionService
32. Repositories

Criar:

SaleRepository

CustomerRepository

PaymentRepository
33. Controllers

Criar:

SaleController

CustomerController

PaymentController

ReportSalesController
34. Permissões Necessárias

Adicionar:

customers.view

customers.create

customers.edit

customers.delete


sales.view

sales.create

sales.edit

sales.cancel


payments.view

payments.receive


reports.sales
35. Auditoria

Registrar:

alteração de preço;
desconto aplicado;
cancelamento;
devolução;
alteração de pagamento.
36. Regras de Negócio
Regra 1

Toda venda deve possuir pelo menos um produto.

Regra 2

Venda confirmada baixa estoque.

Regra 3

Venda cancelada devolve estoque.

Regra 4

Preço vendido pode ser diferente do preço padrão, mas deve ser registrado.

Regra 5

Lucro deve utilizar custo real do produto.

37. Critérios de Aceitação
[ ] Cadastro de clientes

[ ] Venda rápida

[ ] Carrinho

[ ] Alteração de preço

[ ] Desconto

[ ] Pagamentos

[ ] Parcelamento

[ ] Baixa estoque

[ ] Cálculo lucro

[ ] Cancelamento

[ ] Devolução

[ ] Relatórios
Encerramento da Parte 13

O módulo comercial transforma o estoque e os custos de importação em operação de venda real.

Ele permitirá ao proprietário:

vender rapidamente;
saber o lucro de cada negociação;
controlar clientes;
acompanhar a saúde financeira do negócio.