# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 21 — Módulo de Viagens e Controle de Importações

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação funcional do módulo de viagens, despesas internacionais e importação de mercadorias

---

# 1. Objetivo

O módulo de Viagens será responsável pelo controle completo das viagens realizadas pelo vendedor/importador.

O objetivo é permitir registrar:

- viagens internacionais;
- destinos;
- períodos;
- custos envolvidos;
- despesas em diferentes moedas;
- compras realizadas;
- conversão cambial;
- custo real dos produtos importados.

---

# 2. Conceito do Módulo

O processo real do negócio funciona da seguinte forma:


Planejamento da viagem

↓

Compra de passagens

↓

Hospedagem

↓

Alimentação

↓

Deslocamentos

↓

Compra dos produtos

↓

Retorno ao país

↓

Formação do custo

↓

Venda dos produtos


---

# 3. Objetivo Financeiro

O sistema deverá responder:

- Quanto custou a viagem?
- Quanto foi gasto em produtos?
- Qual foi o investimento total?
- Quanto cada produto realmente custou?
- Qual margem de lucro foi obtida?

---

# 4. Tela Principal de Viagens

Menu:


Viagens


---

Tela:


Viagens

[ Nova Viagem ]

Destino

Período

Investimento

Produtos

Status

Ações


---

# 5. Cadastro de Viagem

Campos:


Nome da viagem

País destino

Cidade

Data saída

Data retorno

Objetivo

Moeda principal

Cotação utilizada

Observações


---

Exemplo:


Viagem:

Miami Julho 2026

Destino:

Estados Unidos

Moeda:

USD

Cotação:

R$5,45


---

# 6. Status da Viagem

Estados:


PLANNED

IN_PROGRESS

FINISHED

CANCELED


---

## PLANNED

Viagem planejada.

---

## IN_PROGRESS

Viagem acontecendo.

---

## FINISHED

Viagem concluída.

---

## CANCELED

Cancelada.

---

# 7. Dashboard da Viagem

Ao abrir uma viagem:


Miami Julho 2026

USD Cotação:
R$5,45

Passagens

R$3.500

Hospedagem

R$4.200

Compras

US$8.000

Total Investido

R$51.300


---

# 8. Controle de Moedas

O sistema deverá permitir múltiplas moedas.

Exemplo:

Durante uma viagem:


Dólar

Euro

Real

Peso


---

Cada lançamento deverá possuir:


Valor original

Moeda

Cotação utilizada

Valor convertido


---

# 9. Regra de Conversão

Fórmula:


Valor convertido =
Valor original × Cotação


---

Exemplo:

Compra:


US$500


Cotação:


5,40


Resultado:


R$2.700


---

# 10. Histórico Cambial

O sistema nunca deverá recalcular valores antigos usando nova cotação.

---

Exemplo:

Compra realizada:


10/07/2026

USD:

5,20


---

Cotação atual:


5,80


---

A compra permanece:


5,20


---

# 11. Cadastro de Despesas

Menu:


Despesas da Viagem


---

Categorias padrão:


Passagem aérea

Hospedagem

Alimentação

Transporte

Seguro viagem

Taxas

Bagagem

Outros


---

# 12. Cadastro de Despesa

Campos:


Categoria

Descrição

Data

Valor

Moeda

Cotação

Valor convertido

Comprovante

Observação


---

Exemplo:


Hotel Miami

USD 600

Cotação 5,45

Total:

R$3.270


---

# 13. Anexos de Despesas

Permitir anexar:

- notas;
- recibos;
- comprovantes;
- fotos.

---

Formatos:


PDF

JPG

PNG


---

# 14. Banco de Dados — Despesas

Tabela:


trip_expenses


---

Campos:

```sql
id

tenant_id

trip_id

category_id

description

currency_id

amount

exchange_rate

converted_amount

expense_date

attachment

created_at
15. Compras Internacionais

A viagem poderá possuir diversas compras.

Exemplo:

Viagem Miami

Compra Apple

Compra Eletrônicos

Compra Roupas
16. Cadastro de Compra

Campos:

Fornecedor

Data compra

Moeda

Cotação

Valor total

Observações

Exemplo:

Fornecedor:

Best Buy


Valor:

USD 5.000


Cotação:

5,40
17. Itens da Compra

Cada compra terá:

Produto

Quantidade

Preço unitário

Moeda

Conversão

Total

Exemplo:

Notebook

Quantidade:

10


Preço:

USD 700


Total:

USD 7000
18. Formação do Custo Real

O custo do produto deverá considerar:

Valor compra

+

Parte das despesas da viagem

+

Taxas

+

Outros custos

Fórmula:

Custo real =
Produto convertido
+
Rateio despesas
19. Rateio de Despesas

O sistema deverá permitir escolher:

Rateio proporcional ao valor

Exemplo:

Produto A:

70%

Produto B:

30%
Rateio proporcional à quantidade

Exemplo:

100 produtos:

Cada produto recebe:

1%
Rateio manual

Usuário define.

20. Exemplo de Formação de Custo

Compra:

Notebook

USD700

Cotação:

5,40

Custo compra:

R$3.780

Viagem:

R$10.000

Produtos:

100 unidades

Rateio:

R$100 por produto

Custo final:

R$3.880
21. Integração com Estoque

Ao finalizar compra:

Fluxo:

Compra concluída

↓

Produtos cadastrados

↓

Entrada estoque

↓

Atualização custo
22. Status da Compra

Estados:

DRAFT

CONFIRMED

RECEIVED

CANCELED
DRAFT

Rascunho.

CONFIRMED

Compra realizada.

RECEIVED

Produtos recebidos no estoque.

23. Produtos Sem Cadastro Prévio

Durante a viagem poderá ocorrer:

Produto novo encontrado

Sistema deverá permitir:

Cadastrar rapidamente

ou

Vincular depois
24. Scanner / Foto

Futuro:

Permitir:

fotografar produto;
capturar código;
adicionar rapidamente.
25. Relatórios da Viagem

Gerar:

Resumo financeiro

Contendo:

Total despesas

Total compras

Total investido
Relatório de produtos

Contendo:

Produto

Quantidade

Custo dólar

Custo real

Custo final
Análise de rentabilidade

Contendo:

Valor investido

Valor esperado venda

Lucro estimado
26. Dashboard de Rentabilidade

Mostrar:

Investimento:

R$50.000


Venda estimada:

R$90.000


Lucro estimado:

R$40.000


Margem:

80%
27. Alertas

Criar notificações:

Viagem sem cotação

Despesa sem comprovante

Compra sem produto vinculado

Produto sem custo final
28. Controle de Documentos

Permitir anexar:

passaporte;
notas;
documentos fiscais;
contratos;
comprovantes.
29. Banco de Dados Adicional
trip_categories
id

tenant_id

name

type

status
import_documents
id

tenant_id

trip_id

type

file

description

created_at
30. Services Backend

Criar:

TripService

ExpenseService

PurchaseService

CurrencyConversionService

CostAllocationService
31. Controllers

Criar:

TripController

TripExpenseController

PurchaseController

CostController
32. Regras de Negócio
Regra 1

Toda despesa internacional deve possuir moeda.

Regra 2

Toda conversão deve guardar cotação utilizada.

Regra 3

Produtos importados devem possuir custo final antes da venda.

Regra 4

Viagem finalizada não deve permitir alterações sem registro.

33. Auditoria

Registrar:

alteração de cotação;
alteração de custo;
exclusão de despesa;
cancelamento compra.
34. Critérios de Aceitação
[ ] Criar viagem

[ ] Registrar despesas

[ ] Trabalhar com múltiplas moedas

[ ] Converter valores

[ ] Registrar compras

[ ] Vincular produtos

[ ] Calcular custo real

[ ] Ratear despesas

[ ] Gerar relatórios

[ ] Integrar estoque
Encerramento da Parte 21

O módulo de viagens será um dos diferenciais do ImportControl.

Ele transforma uma simples gestão de produtos em um sistema especializado para pequenos importadores, permitindo descobrir o verdadeiro custo de cada mercadoria e tomar decisões baseadas em lucro real.