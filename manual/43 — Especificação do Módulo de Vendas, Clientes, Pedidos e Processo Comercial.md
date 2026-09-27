# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 43 — Especificação do Módulo de Vendas, Clientes, Pedidos e Processo Comercial

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Gestão comercial, relacionamento com clientes, pedidos, vendas, descontos, pagamentos e integração operacional

---

# 1. Objetivo

O módulo de Vendas será responsável por controlar todo o processo comercial do ImportControl.

O objetivo é permitir que o usuário realize vendas de forma simples, rápida e segura, mantendo integração automática com:


Estoque

Financeiro

Clientes

Produtos

Relatórios

Dashboard


---

# 2. Conceito Geral do Processo Comercial

Fluxo:


Cadastro cliente

↓

Seleção produtos

↓

Negociação preço

↓

Aplicação desconto

↓

Finalização venda

↓

Pagamento

↓

Baixa estoque

↓

Registro financeiro

↓

Atualização indicadores


---

# 3. Objetivos Específicos

O módulo deverá permitir:


Cadastrar clientes

Criar pedidos

Registrar vendas

Alterar preço no momento da venda

Aplicar descontos

Controlar pagamentos

Registrar histórico comercial

Acompanhar lucratividade


---

# 4. Menu Vendas

Estrutura:


Vendas

├── Nova venda

├── Pedidos

├── Vendas realizadas

├── Clientes

├── Orçamentos

├── Devoluções

├── Pagamentos

└── Relatórios comerciais


---

# 5. Cadastro de Clientes

O sistema deverá possuir cadastro completo.

Tipos:


Pessoa Física

Pessoa Jurídica


---

# 6. Dados do Cliente

Campos:


Nome

CPF/CNPJ

Telefone

Email

Data nascimento

Endereço

Cidade

Estado

Observações


---

Tabela:

## customers

```sql
id

tenant_id

name

document

type

phone

email

address

city

state

notes

created_at
7. Classificação de Clientes

Permitir categorizar:

Cliente comum

Cliente frequente

Cliente VIP

Revendedor

Empresa
8. Histórico do Cliente

Cada cliente deverá possuir:

Compras realizadas

Produtos adquiridos

Valor total comprado

Última compra

Ticket médio

Observações comerciais

Tela:

Cliente

├── Dados

├── Compras

├── Pagamentos

├── Histórico

└── Observações
9. Busca de Clientes

Permitir:

Nome

CPF/CNPJ

Telefone

Email
10. Nova Venda

Tela principal:

Cliente

↓

Produtos

↓

Valores

↓

Pagamento

↓

Finalização
11. Seleção de Produtos

Permitir buscar:

Nome

Código

Categoria

Marca

Modelo

Mostrar:

Produto

Estoque disponível

Custo

Preço padrão

Margem
12. Carrinho de Venda

Estrutura:

Produto

Quantidade

Preço unitário

Desconto

Subtotal

Lucro estimado

Exemplo:

iPhone 15

Quantidade: 2

Preço:
R$5.500

Subtotal:
R$11.000
13. Alteração de Preço no Ato da Venda

O sistema deverá permitir alterar o preço durante a venda.

Exemplo:

Preço padrão:

R$5.500

Negociação:

R$5.300

Porém deverá registrar:

Preço original

Preço vendido

Diferença

Usuário responsável

Motivo
14. Controle de Preço Mínimo

Cada produto poderá possuir:

Preço padrão

Preço promocional

Preço mínimo autorizado

Regra:

Venda abaixo do mínimo

↓

Solicitar autorização
15. Descontos

Permitir:

Desconto percentual

Desconto valor fixo

Desconto por item

Desconto total

Exemplo:

Produto:

R$5.000

Desconto:

10%

Venda:

R$4.500
16. Controle de Desconto

Registrar:

Valor original

Valor desconto

Valor final

Usuário

Data
17. Cálculo do Lucro na Venda

Fórmula:

Lucro

=

Preço venda

-

Custo produto

Exemplo:

Venda:

R$6.000


Custo:

R$4.000


Lucro:

R$2.000
18. Venda em Múltiplas Moedas

Embora a venda padrão seja em reais, preparar:

BRL

USD

EUR
19. Finalização da Venda

Após confirmar:

Sistema executa:

Criar venda

↓

Baixar estoque

↓

Registrar pagamento

↓

Criar movimentação financeira

↓

Atualizar cliente

↓

Gerar comprovante
20. Tabela sales
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

created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);
21. Itens da Venda

Tabela:

sale_items
id

sale_id

product_id

quantity

cost_price

original_price

sale_price

discount

subtotal

profit
22. Status da Venda

Estados:

DRAFT

Rascunho


PENDING

Aguardando pagamento


CONFIRMED

Confirmada


COMPLETED

Finalizada


CANCELLED

Cancelada


RETURNED

Devolvida
23. Orçamentos

Permitir criar venda futura.

Fluxo:

Orçamento

↓

Aprovação cliente

↓

Converter em venda

Tabela:

quotes
id

tenant_id

customer_id

status

total

valid_until

created_at
24. Validade do Orçamento

Configurar:

7 dias

15 dias

30 dias

Personalizado
25. Conversão Orçamento → Venda

Ao converter:

Criar venda

Baixar estoque

Registrar financeiro
26. Pagamentos

Permitir:

Dinheiro

PIX

Cartão crédito

Cartão débito

Transferência

Outro
27. Venda Parcelada

Permitir:

Entrada

+

Parcelas futuras

Exemplo:

Venda:

R$10.000


Entrada:

R$3.000


3x R$2.333
28. Tabela Payments
id

sale_id

method

amount

installment

due_date

status

payment_date
29. Contas a Receber

Venda parcelada gera:

Conta futura

Cliente

Valor

Vencimento

Status
30. Devoluções

Permitir:

Devolução total

Devolução parcial

Troca
31. Processo de Devolução

Fluxo:

Solicitação

↓

Análise

↓

Aprovação

↓

Retorno estoque

↓

Ajuste financeiro
32. Tabela Sale Returns
id

sale_id

customer_id

reason

status

amount

created_at
33. Integração Estoque

Venda concluída:

Quantidade vendida

↓

Baixa automática

↓

Histórico movimentação
34. Integração Financeira

Venda concluída:

Receita criada

↓

Fluxo caixa atualizado

↓

Lucro calculado
35. Comissão de Vendedores

Preparar:

Percentual comissão

Valor comissão

Vendedor responsável

Tabela:

commissions
id

sale_id

user_id

percentage

amount

status
36. Ranking Comercial

Criar indicadores:

Maior vendedor

Maior faturamento

Maior lucro

Quantidade vendas
37. Dashboard Comercial

Mostrar:

Vendas hoje

Faturamento mês

Lucro mês

Clientes novos

Produtos mais vendidos
38. Relatórios Comerciais

Criar:

Vendas por período

Vendas por cliente

Vendas por produto

Lucro por venda

Descontos aplicados

Produtos vendidos
39. Auditoria Comercial

Registrar:

Alteração preço

Cancelamento venda

Aplicação desconto

Exclusão pedido

Alteração pagamento
40. Serviços Backend

Criar:

SaleService

CustomerService

PaymentService

QuoteService

ReturnService

CommissionService
41. Controllers

Criar:

SaleController

CustomerController

PaymentController

QuoteController

ReturnController
42. Regras de Negócio
Regra 1

Nenhuma venda confirmada sem estoque disponível.

Regra 2

Toda venda deve possuir cliente.

Regra 3

Alteração de preço deve ser registrada.

Regra 4

Cancelamento deve desfazer:

Financeiro

Estoque

Indicadores
43. Critérios de Aceitação
[ ] Cadastro clientes

[ ] Histórico clientes

[ ] Criação venda

[ ] Carrinho funcionando

[ ] Alteração preço venda

[ ] Controle desconto

[ ] Cálculo lucro

[ ] Pagamentos

[ ] Parcelamentos

[ ] Baixa estoque

[ ] Integração financeira

[ ] Orçamentos

[ ] Devoluções

[ ] Auditoria
Encerramento da Parte 43

O módulo de Vendas será o centro da operação comercial do ImportControl.

Ele conecta diretamente:

Clientes

Produtos

Estoque

Financeiro

Relatórios

Dashboard

Com este módulo, o proprietário terá controle completo desde a negociação até o recebimento financeiro, mantendo histórico de cada operação e garantindo visão real do lucro.