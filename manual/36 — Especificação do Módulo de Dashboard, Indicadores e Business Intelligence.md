# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 36 — Especificação do Módulo de Dashboard, Indicadores e Business Intelligence

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Painel executivo, indicadores estratégicos, análises gerenciais e inteligência de negócio

---

# 1. Objetivo

O módulo de Dashboard e Business Intelligence será responsável por transformar os dados operacionais do sistema em informações estratégicas para tomada de decisão.

O objetivo principal é permitir que o proprietário acompanhe o negócio sem precisar analisar diversos módulos individualmente.

O sistema deverá responder rapidamente:


Quanto vendi?

Quanto lucrei?

Quais produtos mais vendem?

Quais produtos dão mais lucro?

Qual viagem foi mais rentável?

Onde estou perdendo dinheiro?

Como está minha evolução?


---

# 2. Conceito do Dashboard

O Dashboard será uma visão consolidada:


Produtos

Estoque

Compras

Vendas

Financeiro

Clientes

↓

Indicadores

↓

Decisões estratégicas


---

# 3. Perfis de Dashboard

Cada perfil terá uma visão diferente.

---

## 3.1 Dashboard Administrador

Visão completa:


Financeiro

Produtos

Estoque

Vendas

Viagens

Lucro


---

## 3.2 Dashboard Gerente

Foco operacional:


Vendas

Estoque

Produtos

Clientes


---

## 3.3 Dashboard Vendedor

Foco comercial:


Minhas vendas

Metas

Produtos disponíveis

Clientes


---

# 4. Estrutura do Dashboard Principal

Layout:


ImportControl

Período:
[Hoje] [Semana] [Mês] [Ano]

Cards principais

Faturamento

Lucro

Despesas

Estoque

Gráficos

Últimas vendas

Alertas

Produtos destaque


---

# 5. Cards Principais (KPIs)

Criar cards:


Faturamento

Lucro líquido

Margem média

Produtos vendidos

Valor estoque

Clientes ativos


---

# 6. KPI Faturamento

Exibir:


Total vendido no período


---

Exemplo:


Agosto/2026

R$75.000


---

Comparativo:


↑ 15%

em relação ao mês anterior


---

# 7. KPI Lucro Líquido

Fórmula:


Receitas

Custos

Despesas

=

Lucro líquido


---

Exemplo:


Lucro:

R$25.000


---

# 8. KPI Margem

Mostrar:


Percentual médio de lucro


---

Exemplo:


33,3%


---

# 9. KPI Estoque

Exibir:


Quantidade produtos

Valor investido

Produtos parados

Produtos baixo estoque


---

Exemplo:


Estoque:

R$120.000


---

# 10. KPI Clientes

Mostrar:


Clientes cadastrados

Novos clientes

Clientes recorrentes

Ticket médio


---

# 11. Filtros Globais

Todo dashboard deverá possuir:


Período

Data inicial

Data final

Produto

Categoria

Viagem

Vendedor

Cliente


---

# 12. Gráfico de Evolução de Vendas

Tipo:


Linha


---

Mostrar:


Vendas por dia

Vendas por semana

Vendas por mês


---

Exemplo:


Janeiro

R$40.000

Fevereiro

R$55.000

Março

R$70.000


---

# 13. Gráfico Receita x Despesa

Tipo:


Colunas comparativas


---

Mostrar:


Receitas

Despesas

Saldo


---

Objetivo:

Identificar crescimento ou prejuízo.

---

# 14. Gráfico de Lucro por Produto

Mostrar:


Produto

Quantidade vendida

Lucro gerado


---

Exemplo:


iPhone

Lucro:

R$20.000

AirPods

Lucro:

R$8.000


---

# 15. Ranking de Produtos

Criar ranking:


Mais vendidos

Mais lucrativos

Menor giro

Maior estoque


---

# 16. Produtos Mais Vendidos

Exibir:


Posição

Produto

Quantidade

Receita


---

Exemplo:


1º iPhone

50 unidades

2º Apple Watch

30 unidades


---

# 17. Produtos Mais Rentáveis

Diferente de venda.

Analisar:


Produto

Margem

Lucro total


---

Exemplo:

Produto A:


Vende pouco

Mas gera muito lucro


---

# 18. Produtos com Baixo Giro

Identificar:


Produtos sem venda

Dias parado

Valor investido


---

Exemplo:


Notebook X

90 dias parado

R$15.000 investidos


---

# 19. Análise de Estoque

Indicadores:


Valor total estoque

Produtos encalhados

Giro médio

Estoque crítico


---

# 20. Dashboard de Viagens

Criar visão específica:


Viagens realizadas

Investimento total

Compras

Despesas

Retorno


---

# 21. Comparativo de Viagens

Tabela:


Viagem

Investimento

Venda gerada

Lucro

ROI


---

Exemplo:


Miami 2026

Investimento:

R$30.000

Retorno:

R$70.000

ROI:

133%


---

# 22. Cálculo ROI

Fórmula:


ROI

=

(Lucro / Investimento)

×100


---

# 23. Dashboard Financeiro

Mostrar:


Saldo atual

Entradas

Saídas

Contas vencendo

Previsão futura


---

# 24. Previsão Financeira

Preparar:

Analisar:


Contas futuras

Recebimentos previstos

Despesas recorrentes


---

Exemplo:


Próximos 30 dias:

Entrada esperada:

R$20.000

Saídas:

R$8.000


---

# 25. Dashboard de Clientes

Indicadores:


Clientes ativos

Clientes recorrentes

Maior comprador

Ticket médio


---

# 26. Ranking de Clientes

Mostrar:


Cliente

Compras

Valor gasto

Lucro gerado


---

# 27. Análise de Ticket Médio

Fórmula:


Ticket médio

=

Valor total vendas

/

Quantidade vendas


---

# 28. Alertas Inteligentes

Criar área:


Atenção necessária


---

Exemplos:


Produto abaixo do estoque mínimo

Conta vencendo amanhã

Produto parado há 90 dias

Margem abaixo do esperado


---

# 29. Sistema de Notificações

Tipos:


Informativo

Aviso

Crítico


---

Exemplo:


⚠ Estoque do produto X está acabando


---

# 30. Dashboard Mobile

O sistema deverá ser responsivo.

Priorizar:


Celular

Tablet

Desktop


---

No celular:

Mostrar:


Cards

Gráficos simples

Alertas

Últimas vendas


---

# 31. Gráficos Recomendados

Biblioteca sugerida:


Chart.js


---

Tipos:


Linha

Barra

Pizza

Área

Radar


---

# 32. Banco de Dados para BI

Criar estrutura preparada:

## dashboard_cache

```sql
id

tenant_id

metric

value

period

created_at

Objetivo:

Evitar consultas pesadas.

33. Métricas Pré-calculadas

Gerar:

Faturamento diário

Lucro diário

Quantidade vendida

Estoque total

Clientes ativos
34. Rotina de Atualização

Criar processo:

CRON

↓

Calcula indicadores

↓

Atualiza cache

↓

Dashboard carrega rápido
35. Serviços Backend

Criar:

DashboardService

KpiService

ReportService

AnalyticsService

MetricCacheService
36. Controllers

Criar:

DashboardController

ReportController

AnalyticsController
37. Segurança

Controlar:

Quem pode visualizar lucro

Quem pode visualizar custos

Quem pode exportar relatórios

Exemplo:

Vendedor:

Visualiza somente suas vendas

Administrador:

Visualiza tudo
38. Exportação de Dados

Permitir:

PDF

Excel

CSV

Relatórios:

Financeiro

Produtos

Vendas

Viagens

Clientes
39. Funcionalidades Futuras de IA

Preparar integração:

Análise automática de vendas

Sugestão de compra

Previsão de demanda

Identificação de produtos rentáveis

Exemplo:

Sistema:

"O produto AirPods vende 40% mais rápido que a média. Considere comprar novamente."
40. Critérios de Aceitação
[ ] Dashboard carregando

[ ] KPIs funcionando

[ ] Gráficos funcionando

[ ] Filtros funcionando

[ ] Comparativos funcionando

[ ] Ranking produtos

[ ] Ranking clientes

[ ] Indicadores financeiros

[ ] Alertas funcionando

[ ] Exportação disponível
Encerramento da Parte 36

O módulo de Dashboard transforma o ImportControl em uma ferramenta estratégica.

O sistema deixa de apenas registrar informações e passa a auxiliar decisões:

Comprar melhor

Vender melhor

Investir melhor

Controlar melhor

Lucrar mais

Com esse módulo, o proprietário terá uma visão profissional do negócio, semelhante a sistemas ERP de grandes empresas.