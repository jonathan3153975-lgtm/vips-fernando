# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 4 — Regras de Negócio do Sistema

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Regras funcionais e operacionais do negócio

---

# 1. Objetivo

Esta seção define todas as regras de funcionamento do ImportControl.

As regras descritas aqui representam o comportamento esperado do sistema independentemente da tecnologia utilizada.

Toda implementação deverá respeitar estas definições.

---

# 2. Conceito Fundamental do Negócio

O ImportControl foi desenvolvido para controlar um modelo de negócio baseado em:
Compra internacional

↓

Transporte da mercadoria

↓

Entrada no Brasil

↓

Formação do custo real

↓

Estoque

↓

Venda

↓

Apuração do lucro


O sistema deverá considerar que o valor pago no produto no exterior **não representa o custo final**.

O custo real deverá considerar:

- preço de aquisição;
- conversão monetária;
- despesas da viagem;
- impostos;
- taxas;
- custos adicionais.

---

# 3. Ciclo Operacional Completo

O fluxo principal será:


1 - Criar viagem

2 - Definir moedas e cotações

3 - Registrar despesas

4 - Registrar fornecedores

5 - Registrar compras

6 - Criar lotes

7 - Realizar rateio dos custos

8 - Disponibilizar estoque

9 - Realizar vendas

10 - Controlar recebimentos

11 - Gerar indicadores

12 - Encerrar viagem


---

# 4. Entidade Principal: Viagem

A viagem representa o evento de importação.

Toda compra internacional deverá obrigatoriamente estar vinculada a uma viagem.

---

# 5. Status da Viagem

Uma viagem possui estados:


PLANNING

OPEN

IMPORTING

RETURNED

CLOSED

CANCELED


---

## PLANNING

Viagem criada, porém ainda não iniciada.

Permite:

- alterar dados;
- cadastrar previsão de gastos.

---

## OPEN

Viagem em andamento.

Permite:

- cadastrar despesas;
- cadastrar compras;
- cadastrar fornecedores.

---

## IMPORTING

Produtos comprados e aguardando retorno.

---

## RETURNED

Mercadorias chegaram.

Permite:

- conferência;
- entrada no estoque;
- cálculo de custos.

---

## CLOSED

Viagem encerrada.

Não permite alterações comuns.

Alterações somente por usuário autorizado.

---

# 6. Dados da Viagem

Campos obrigatórios:

Nome da viagem

País

Cidade

Data de saída

Data de retorno

Moeda principal

Cotação utilizada

Responsável

Status

Observações

---

# 7. Cotação de Moedas

O sistema deverá controlar moedas utilizadas durante a operação.

Exemplos:


USD

EUR

GBP

BRL


---

# 8. Regra de Cotação

Toda conversão deverá armazenar a cotação utilizada no momento do lançamento.

Nunca depender apenas da cotação atual.

Exemplo:

Compra realizada em:

10/01/2026

Dólar:

R$ 5,20

Mesmo que posteriormente o dólar esteja:

R$ 6,00

O produto continuará utilizando:

R$ 5,20

---

# 9. Cadastro de Moedas

Estrutura preparada para:

- dólar americano;
- euro;
- libra;
- peso;
- outras moedas.

---

# 10. Despesas da Viagem

Todas as despesas deverão estar vinculadas a uma viagem.

Exemplos:

- passagem aérea;
- hospedagem;
- alimentação;
- transporte;
- combustível;
- seguro;
- bagagem;
- impostos;
- taxas;
- outros.

---

# 11. Dados de uma Despesa

Campos:

Categoria

Descrição

Data

Valor original

Moeda

Cotação

Valor convertido

Documento

Observação

---

# 12. Conversão Automática

Exemplo:

Despesa:

Hotel

Valor:

500 USD

Cotação:

5,40

Sistema calcula:


500 x 5,40

= R$ 2.700


---

# 13. Despesas Não Rateáveis

Algumas despesas poderão ser apenas registradas.

Exemplo:

Alimentação pessoal.

Estas despesas:

- aparecem no financeiro;
- não entram no custo do produto.

---

# 14. Despesas Rateáveis

Custos relacionados à importação.

Exemplo:

- passagem;
- transporte;
- frete;
- seguro;
- taxas.

Estas despesas poderão compor o custo dos produtos.

---

# 15. Produtos

Todo produto comprado deverá pertencer a:

- uma viagem;
- um fornecedor;
- um lote.

---

# 16. Cadastro do Produto

Informações:

Código interno

SKU

Código de barras

Nome

Categoria

Marca

Modelo

Descrição

Fornecedor

Foto

Peso

Dimensão

Observações

---

# 17. Compra do Produto

A compra deverá registrar:

Quantidade

Valor unitário

Moeda

Cotação

Valor convertido

Valor total

---

Exemplo:

Produto:

Apple Watch

Quantidade:

10

Preço:

300 USD

Cotação:

5,40

Custo:

R$ 1.620 unidade

---

# 18. Conceito de Lote

Todo conjunto de produtos adquiridos em uma mesma compra deverá gerar um lote.

Exemplo:


Lote:

MIAMI-2026-001

Produtos:

50 iPhones

20 AirPods

10 Apple Watch


---

# 19. Objetivo dos Lotes

Permitir:

- rastreamento da origem;
- cálculo real de custo;
- análise de rentabilidade;
- controle histórico.

---

# 20. Custo Real do Produto

O custo real será:


Custo aquisição

Custos adicionais rateados

=

Custo real


---

Exemplo:

Produto:

R$ 1.000

Rateio:

R$ 200

Custo final:

R$ 1.200

---

# 21. Sistema de Rateio

O sistema deverá possuir quatro formas.

---

## Rateio por quantidade

Divide igualmente entre unidades.

Exemplo:

100 produtos

Despesa:

R$ 1.000

Cada produto:

R$ 10

---

## Rateio proporcional ao valor

Produtos mais caros recebem maior custo.

---

## Rateio por peso

Útil para produtos volumosos.

---

## Rateio manual

Usuário define valores.

---

# 22. Fechamento de Custos

Antes da venda, o produto deverá possuir:


Custo original

Custos adicionais

=

Custo final


Sem custo final definido, não poderá ser utilizado em relatórios de lucro.

---

# 23. Controle de Estoque

O estoque será movimentado por eventos.

Nunca alterar quantidade diretamente.

---

# 24. Movimentos de Estoque

Tipos:


ENTRY

SALE

ADJUSTMENT

LOSS

RETURN


---

# 25. Entrada

Criada quando:

- produto chega da viagem;
- lote é liberado.

---

# 26. Venda

Ao vender:

Sistema:

- reduz estoque;
- cria movimentação;
- calcula lucro;
- registra cliente.

---

# 27. Ajuste

Utilizado para:

- correção;
- inventário;
- divergência.

Obrigatório informar motivo.

---

# 28. Venda

Uma venda poderá possuir:

- vários produtos;
- vários pagamentos;
- descontos;
- observações.

---

# 29. Preço de Venda

O produto possui:

Preço sugerido

Preço mínimo

Preço promocional

---

Porém:

No momento da venda o usuário poderá alterar.

---

# 30. Histórico de Preço

Toda alteração deverá registrar:

Preço anterior

Preço novo

Usuário

Data

Motivo

---

# 31. Regra de Lucro

O lucro será calculado:


Preço vendido

Custo real

=

Lucro bruto


---

# 32. Margem

Cálculo:


Lucro / Venda x 100


---

# 33. Markup

Cálculo:


Preço venda / custo


---

# 34. Desconto

Descontos deverão ser registrados.

Nunca alterar o custo.

---

# 35. Clientes

Uma venda deverá possuir cliente.

Para vendas rápidas poderá existir:


Cliente consumidor final


---

# 36. Pagamentos

Uma venda poderá possuir vários pagamentos.

Exemplo:

Venda:

R$ 1.000

Pagamento:

PIX

R$ 600

Cartão

R$ 400

---

# 37. Formas de Pagamento

Inicialmente:


PIX

DINHEIRO

CARTAO_CREDITO

CARTAO_DEBITO

TRANSFERENCIA

OUTROS


---

# 38. Financeiro

Toda movimentação deverá gerar impacto financeiro.

---

# 39. Entradas

Exemplos:

Venda

Recebimento

---

# 40. Saídas

Exemplos:

Despesa viagem

Compra

Taxa

---

# 41. Fluxo de Caixa

O sistema deverá demonstrar:

Saldo inicial

Entradas

Saídas

Saldo final

---

# 42. DRE da Viagem

Cada viagem deverá possuir relatório financeiro próprio.

Modelo:


Receita total

(-) Custo dos produtos vendidos

(-) Despesas da viagem

=

Lucro líquido


---

# 43. ROI da Viagem

Cálculo:


Lucro líquido

/

Investimento total

x

100


---

# 44. Encerramento da Viagem

Ao fechar:

Sistema gera:

- resumo financeiro;
- lucro;
- estoque restante;
- produtos vendidos;
- produtos pendentes.

---

# 45. Alterações após fechamento

Somente:

Developer

ou

Administrador autorizado

---

# 46. Auditoria Obrigatória

Registrar alterações em:

- preço;
- estoque;
- custo;
- vendas;
- despesas;
- fechamento.

---

# 47. Regras Gerais

Nunca:

- excluir venda com estoque movimentado;
- alterar custo manualmente sem histórico;
- apagar movimentações financeiras;
- remover produtos vendidos.

---

# 48. Objetivo Final das Regras

O sistema deverá permitir responder perguntas como:

"Quanto realmente custou esse produto?"

"Qual viagem foi mais lucrativa?"

"Quanto ganhei neste mês?"

"Quais produtos dão maior margem?"

"Quanto tenho parado em estoque?"

"Qual meu lucro real descontando todos os custos?"

---

# Encerramento da Parte 4

Esta seção define o comportamento operacional do ImportControl.

Todas as próximas implementações deverão seguir estas regras.

---

## Próxima Parte (Parte 5)

**Modelagem Completa do Banco de Dados**

Será detalhado:

- modelo relacional;
- tabelas;
- campos;
- tipos;
- índices;
- chaves estrangeiras;
- relacionamentos;
- normalização;
- migrations;
- ERD (Entity Relationship Diagram).