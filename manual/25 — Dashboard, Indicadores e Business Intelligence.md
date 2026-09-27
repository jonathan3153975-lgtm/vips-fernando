# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 25 — Dashboard, Indicadores e Business Intelligence

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação do painel executivo, indicadores estratégicos e análise de dados

---

# 1. Objetivo

O módulo de Dashboard e Business Intelligence será responsável por transformar os dados operacionais do sistema em informações estratégicas para tomada de decisão.

O objetivo é permitir que o proprietário visualize rapidamente:

- desempenho do negócio;
- evolução das vendas;
- lucratividade;
- estoque;
- produtos mais rentáveis;
- desempenho das viagens;
- situação financeira.

---

# 2. Conceito do Dashboard

O dashboard deverá funcionar como um painel executivo.

O usuário deverá conseguir responder rapidamente:


Estou lucrando?

O que está vendendo mais?

Qual produto vale a pena comprar novamente?

Onde estou perdendo dinheiro?

Quanto dinheiro tenho disponível?

Qual viagem trouxe maior retorno?


---

# 3. Perfis de Dashboard

Criar dashboards diferentes conforme o acesso.

---

## Dashboard Proprietário

Visão completa:


Financeiro

Vendas

Estoque

Produtos

Viagens

Lucro


---

## Dashboard Vendedor

Visão operacional:


Vendas

Clientes

Produtos disponíveis

Metas


---

## Dashboard Desenvolvedor SaaS

Visão da plataforma:


Empresas

Usuários

Assinaturas

Uso do sistema


---

# 4. Tela Inicial do Sistema

Após login:


Bem-vindo, João

Resumo de hoje

Vendas Hoje

R$5.500

Pedidos

8

Lucro estimado

R$1.800

Produtos vendidos

15


---

# 5. Cards Principais

Criar cards:

---

## Faturamento do Período

Mostrar:


Valor vendido


Exemplo:


Agosto

R$120.000


---

## Lucro Líquido

Mostrar:


Receita

Custos

Despesas


---

## Estoque

Mostrar:


Valor estoque

Produtos ativos

Produtos baixo estoque


---

## Clientes

Mostrar:


Novos clientes

Clientes ativos

Clientes recorrentes


---

# 6. Filtros Globais

Todos dashboards deverão possuir:


Período

Data inicial

Data final

Categoria

Produto

Viagem

Cliente


---

Exemplo:


Analisar:

01/08/2026

até

31/08/2026


---

# 7. Gráfico de Faturamento

Gráfico:


Evolução das vendas


---

Visual:


Jan ████

Fev ██████

Mar █████████

Abr ███████


---

Permitir:


Diário

Semanal

Mensal

Anual


---

# 8. Gráfico Receita x Despesa

Objetivo:

Comparar:


Quanto entrou

x

Quanto saiu


---

Exemplo:


Receita:

R$100.000

Despesa:

R$60.000


---

# 9. Indicador de Lucro

Mostrar:


Lucro absoluto

Margem percentual


---

Exemplo:


Lucro:

R$40.000

Margem:

40%


---

# 10. Indicador ROI das Viagens

Objetivo:

Avaliar retorno das importações.

---

Fórmula:


ROI =

(Lucro da viagem ÷ Investimento) × 100


---

Exemplo:

Viagem:


Miami 2026


Investimento:


R$80.000


---

Lucro:


R$40.000


---

ROI:


50%


---

# 11. Ranking de Produtos

Mostrar:


Produtos mais vendidos


---

Dados:


Produto

Quantidade

Receita

Lucro


---

Exemplo:


1º AirPods

150 unidades

Lucro R$20.000


---

# 12. Ranking de Produtos Mais Lucrativos

Importante:

Nem sempre vende mais quem gera mais lucro.

Mostrar:


Maior margem

Maior lucro total


---

Exemplo:

Produto A:


Venda:

100 unidades

Lucro:

R$10.000


---

Produto B:


Venda:

20 unidades

Lucro:

R$15.000


---

# 13. Produtos com Baixo Desempenho

Identificar:


Produtos parados

Baixa saída

Baixa margem


---

Exemplo:


Produto:

Notebook X

Última venda:

120 dias


---

Sugestão:


Criar promoção


---

# 14. Indicador de Giro de Estoque

Objetivo:

Saber velocidade de venda.

---

Fórmula:


Produtos vendidos

÷

Estoque médio


---

Classificação:


Alto giro

Normal

Baixo giro


---

# 15. Valor Atual do Estoque

Mostrar:


Quantidade disponível

x

Custo real


---

Exemplo:


Estoque:

200 produtos

Valor:

R$150.000


---

# 16. Alertas Inteligentes

Criar área:


Atenção


---

Exemplos:


5 produtos abaixo do estoque mínimo

3 clientes inadimplentes

Conta vencendo amanhã

Produto sem venda há 90 dias


---

# 17. Indicadores de Clientes

Mostrar:


Novos clientes

Clientes ativos

Clientes recorrentes

Ticket médio


---

# 18. Ticket Médio

Fórmula:


Total vendido

÷

Quantidade vendas


---

Exemplo:


Faturamento:

R$50.000

Vendas:

100

Ticket médio:

R$500


---

# 19. Clientes Mais Valiosos

Ranking:


Cliente

Quantidade compras

Valor total

Lucro gerado


---

Objetivo:

Identificar clientes importantes.

---

# 20. Taxa de Recompra

Mostrar:


Clientes que voltaram a comprar


---

Indicador:


Clientes recorrentes %


---

# 21. Análise por Viagem

Dashboard:


Viagem

Investimento

Produtos comprados

Venda gerada

Lucro

ROI


---

Exemplo:


Miami Julho

Investimento:

R$100.000

Retorno:

R$170.000

Lucro:

R$70.000


---

# 22. Comparativo de Viagens

Permitir comparar:


Viagem A

x

Viagem B


---

Exemplo:


Miami

ROI 70%

Europa

ROI 35%


---

# 23. Indicadores Financeiros Avançados

Criar:

## Margem Bruta


Venda - custo produto


---

## Margem Líquida


Venda

Custos

Despesas


---

## Ponto de Equilíbrio

Mostrar:


Quanto precisa vender
para pagar despesas


---

# 24. Previsão de Resultado

Preparar estrutura futura:


Estimativa de faturamento

Estimativa de lucro

Tendência


---

Base:

- histórico vendas;
- sazonalidade;
- estoque.

---

# 25. Exportação do Dashboard

Permitir:


PDF

Excel

Imagem


---

Exemplo:


Relatório Executivo Agosto/2026


---

# 26. Relatórios Automatizados

Permitir agendamento:


Enviar todo dia 01

Relatório mensal


---

Destino:


Email

WhatsApp (futuro)


---

# 27. Componentes Visuais

Utilizar:


Cards modernos

Gráficos interativos

Filtros rápidos

Indicadores coloridos

Tabelas responsivas


---

# 28. Biblioteca Front-end Recomendada

Compatível com:


Bootstrap 5

JavaScript

Chart.js

DataTables

SweetAlert


---

# 29. Gráficos Recomendados

Implementar:

## Linha

Para:


Evolução vendas


---

## Barras

Para:


Produtos

Categorias


---

## Pizza

Para:


Distribuição despesas


---

## Área

Para:


Crescimento financeiro


---

# 30. Cache de Indicadores

Dashboards pesados deverão utilizar cache.

Exemplo:


Atualização a cada 15 minutos


---

Objetivo:

Melhorar performance.

---

# 31. Banco de Dados Analítico

Preparar futuramente:


Data Warehouse


---

Estrutura:


Fatos

Dimensões


---

Exemplo:

Fato:


Venda


Dimensões:


Produto

Cliente

Data

Viagem


---

# 32. Tabelas de Apoio

Criar futuramente:

## dashboard_cache

```sql
id

tenant_id

metric

value

expires_at
reports_schedule
id

tenant_id

report_type

frequency

email

status
33. Services Backend

Criar:

DashboardService

MetricService

ReportService

AnalyticsService

RankingService
34. Controllers

Criar:

DashboardController

ReportController

AnalyticsController
35. API Interna do Dashboard

Preparar endpoints:

GET /dashboard/summary

GET /dashboard/sales

GET /dashboard/products

GET /dashboard/financial

GET /dashboard/trips
36. Performance

Regras:

evitar consultas repetidas;
utilizar índices;
criar consultas agregadas;
utilizar cache.
37. Segurança

Dashboard deverá respeitar:

tenant_id

permissões

perfil usuário

Usuário nunca poderá visualizar dados de outra empresa.

38. Auditoria

Registrar:

exportações;
relatórios gerados;
acesso a dados sensíveis.
39. Critérios de Aceitação
[ ] Dashboard principal

[ ] Indicadores financeiros

[ ] Indicadores vendas

[ ] Indicadores estoque

[ ] Indicadores clientes

[ ] Análise viagens

[ ] Ranking produtos

[ ] Alertas

[ ] Relatórios PDF

[ ] Exportação Excel

[ ] Filtros

[ ] Controle de acesso
Encerramento da Parte 25

O módulo de Dashboard e Business Intelligence transforma o ImportControl em uma ferramenta de decisão estratégica.

O sistema deixa de apenas registrar informações e passa a responder:

O que vender?

Quando comprar?

Quanto investir?

Qual viagem vale a pena?

Onde está o lucro?

Esse módulo será fundamental para transformar dados operacionais em crescimento do negócio.