# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 34 — Especificação do Módulo de Vendas, Pedidos e Atendimento ao Cliente

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Processo comercial, registro de vendas, pedidos, clientes, pagamentos e integração operacional

---

# 1. Objetivo

O módulo de Vendas será responsável por controlar toda operação comercial do negócio.

Ele deverá permitir que o vendedor consiga:


Localizar produtos rapidamente

Registrar uma venda

Alterar preços quando autorizado

Aplicar descontos

Controlar pagamentos

Atualizar estoque automaticamente

Calcular lucro real


---

# 2. Conceito da Venda

A venda representa a transformação do estoque em receita.

Fluxo:


Cliente

↓

Seleção produtos

↓

Definição preço

↓

Pagamento

↓

Baixa estoque

↓

Registro financeiro

↓

Cálculo lucro


---

# 3. Objetivos do Módulo

Permitir:


Criar vendas

Gerenciar pedidos

Cadastrar clientes

Aplicar descontos

Controlar pagamentos

Emitir comprovantes

Analisar vendas


---

# 4. Menu Vendas

Estrutura:


Vendas

├── Nova venda

├── Lista de vendas

├── Pedidos pendentes

├── Clientes

├── Pagamentos

├── Devoluções

├── Relatórios

└── Configurações comerciais


---

# 5. Tipos de Venda

O sistema deverá suportar:


Venda à vista

Venda parcelada

Venda fiado

Venda online (futuro)

Venda com entrega (futuro)


---

# 6. Processo Principal de Venda

Fluxo:


Usuário inicia venda

↓

Seleciona cliente

↓

Adiciona produtos

↓

Sistema calcula valores

↓

Usuário confirma preço

↓

Seleciona pagamento

↓

Finaliza venda

↓

Atualiza estoque

↓

Gera financeiro


---

# 7. Tela Nova Venda

A tela deverá ser otimizada para velocidade.

Layout:


Nova Venda

Cliente:

[ Buscar cliente ]

Produto:

[ Buscar produto ]

Itens adicionados

Produto | Qtd | Preço | Total

Resumo

Subtotal

Desconto

Total

[ Finalizar Venda ]


---

# 8. Busca de Produtos

Permitir busca por:


Nome

Código interno

Código de barras

Categoria

Fornecedor


---

# 9. Seleção Rápida de Produto

Ao selecionar:

Mostrar:


Imagem

Nome

Estoque disponível

Preço padrão

Último preço vendido


---

# 10. Carrinho de Venda

Cada item deverá possuir:


Produto

Quantidade

Preço unitário

Desconto

Subtotal

Lucro estimado


---

Exemplo:


Produto:

AirPods

Quantidade:

2

Preço:

R$900

Total:

R$1.800


---

# 11. Alteração de Quantidade

Permitir:


Aumentar quantidade

Reduzir quantidade

Remover item


---

Validação:


Quantidade disponível >= quantidade vendida


---

# 12. Alteração de Preço na Venda

Uma característica essencial do sistema.

O usuário poderá alterar:


Preço padrão

↓

Preço negociado


---

Exemplo:

Produto:


Preço padrão:

R$1.200


Cliente negocia:


Preço final:

R$1.100


---

Registrar:


Preço original

Preço aplicado

Diferença

Usuário responsável


---

# 13. Controle de Permissão de Alteração

Regras:

## Vendedor

Pode:


Alterar dentro do limite permitido


---

## Gerente

Pode:


Aplicar descontos maiores


---

## Administrador

Pode:


Qualquer alteração


---

# 14. Descontos

Tipos:


Percentual

Valor fixo


---

Exemplo:

Valor:


R$2.000


Desconto:


10%


Resultado:


R$1.800


---

# 15. Limite de Desconto

Cada perfil terá limite.

Exemplo:


Vendedor:

10%

Gerente:

30%

Administrador:

Sem limite


---

# 16. Aprovação de Venda

Caso ultrapasse limite:

Fluxo:


Vendedor solicita desconto

↓

Gerente recebe aviso

↓

Aprova ou rejeita

↓

Venda continua


---

# 17. Clientes

A venda deverá permitir:


Cliente cadastrado

Cliente consumidor final

Novo cliente durante venda


---

# 18. Cadastro Rápido de Cliente

Durante venda:

Campos mínimos:


Nome

Telefone

Email


---

Depois:

Completar cadastro.

---

# 19. Histórico do Cliente

Mostrar:


Compras realizadas

Valor total comprado

Última compra

Produtos favoritos

Débitos


---

# 20. Cadastro Completo de Cliente

Campos:


Nome

Tipo pessoa

CPF/CNPJ

Telefone

Email

Endereço

Cidade

Estado

Observações


---

# 21. Pedidos

Preparar estrutura para:


Venda iniciada

Venda aguardando pagamento

Venda separada

Venda finalizada


---

# 22. Status da Venda

Estados:


DRAFT

Rascunho

PENDING_PAYMENT

Aguardando pagamento

PAID

Pago

COMPLETED

Concluída

CANCELLED

Cancelada

RETURNED

Devolvida


---

# 23. Banco de Dados

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

payment_status

created_at

updated_at
24. Itens da Venda
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
25. Cálculo do Lucro

Fórmula:

Lucro

=

Preço venda

-

Custo produto

Exemplo:

Venda:

R$900


Custo:

R$600


Lucro:

R$300
26. Lucro Real da Venda

Considerar:

Preço vendido

-

Custo produto

-

Descontos

-

Custos adicionais
27. Pagamentos

O sistema deverá controlar:

Dinheiro

PIX

Cartão débito

Cartão crédito

Transferência

Outros
28. Venda Parcelada

Permitir:

Número parcelas

Valor parcela

Vencimento

Status pagamento

Exemplo:

Venda:

R$3.000


3 parcelas:

R$1.000
29. Contas a Receber

Venda parcelada gera:

Conta a receber

Fluxo:

Venda

↓

Financeiro

↓

Parcelas

↓

Recebimentos
30. Cancelamento de Venda

Permitir:

Cancelar venda

Motivo obrigatório

Ao cancelar:

Retornar estoque

Estornar financeiro

Registrar histórico
31. Devoluções

Fluxo:

Cliente devolve produto

↓

Registrar devolução

↓

Produto retorna estoque

↓

Gerar crédito ou reembolso
32. Comprovante de Venda

Gerar:

PDF

Impressão

Envio futuro WhatsApp

Informações:

Empresa

Cliente

Produtos

Valores

Pagamento

Data
33. Dashboard Comercial

Mostrar:

Vendas hoje

Faturamento

Lucro

Produtos mais vendidos

Clientes recentes
34. Relatórios de Venda

Criar:

Vendas por período

Filtros:

Data inicial

Data final

Usuário

Cliente
Produtos vendidos

Mostrar:

Produto

Quantidade

Receita

Lucro
Ranking vendedores

Mostrar:

Usuário

Quantidade vendas

Valor vendido

Lucro gerado
35. Comissão de Vendedores (Futuro)

Preparar:

Percentual comissão

Meta mensal

Premiação

Tabela futura:

seller_commissions
id

user_id

sale_id

percentage

amount
36. Integração com Estoque

Venda concluída:

Venda

↓

Baixa estoque

↓

Movimentação criada
37. Integração com Financeiro

Venda concluída:

Venda

↓

Receita financeira

↓

Fluxo de caixa
38. Integração com Clientes

Atualizar:

Última compra

Total comprado

Histórico
39. Serviços Backend

Criar:

SaleService

CartService

PaymentService

DiscountService

CustomerService

ReturnService
40. Controllers

Criar:

SaleController

CustomerController

PaymentController

ReturnController

SaleReportController
41. Regras de Segurança

Controlar:

Quem cria venda

Quem altera preço

Quem cancela

Quem concede desconto

Quem visualiza lucro
42. Auditoria

Registrar:

Venda criada

Preço alterado

Desconto aplicado

Venda cancelada

Pagamento alterado
43. Funcionalidades Futuras

Preparar:

Venda pelo celular

Leitor código barras

Integração WhatsApp

Marketplace

Link de pagamento

CRM de clientes
44. Critérios de Aceitação
[ ] Criar venda

[ ] Adicionar produtos

[ ] Alterar preço autorizado

[ ] Aplicar desconto

[ ] Registrar pagamento

[ ] Atualizar estoque

[ ] Gerar financeiro

[ ] Calcular lucro

[ ] Histórico cliente

[ ] Relatórios
Encerramento da Parte 34

O módulo de Vendas será o centro da operação comercial.

Ele deverá transformar o processo:

Produto importado

↓

Estoque disponível

↓

Venda realizada

↓

Dinheiro recebido

↓

Lucro conhecido

Com esse módulo, o proprietário terá controle completo sobre a operação comercial e poderá tomar decisões baseadas em dados reais.