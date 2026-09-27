# Baseline Unica do MVP

**Projeto:** ImportControl  
**Status:** baseline canonica inicial para implementacao  
**Data:** 2026-08-01

---

## 1. Objetivo desta baseline

Este documento substitui a ambiguidade entre versoes sobrepostas e define a referencia unica para inicio do desenvolvimento do MVP.

---

## 2. Stack oficial do MVP

- Backend: PHP 8.3+
- Banco: MariaDB 11+ com compatibilidade MySQL 8+
- Acesso a dados: PDO
- Padrao: MVC + Service Layer + Repository Pattern
- Frontend: server-rendered views com Bootstrap 5 e JavaScript progressivo
- Interacoes assicronas: jQuery/AJAX apenas quando houver ganho claro de UX
- Autenticacao do MVP: sessao web segura com RBAC por tenant
- API interna: rotas HTTP versionadas preparadas para evolucao
- Testes iniciais: PHPUnit

Decisao arquitetural:

No MVP, a autenticacao por sessao sera o mecanismo oficial da interface web. JWT fica reservado para integracoes futuras e APIs externas quando houver caso de uso real.

---

## 3. Escopo fechado do MVP

Entram no MVP:

1. Fundacao tecnica do projeto.
2. Cadastro de tenant e configuracoes basicas.
3. Autenticacao, usuarios, perfis e permissoes.
4. Importacoes/viagens com despesas e cotacao manual.
5. Cadastro de produtos e fornecedores essenciais.
6. Entrada de estoque por lote vinculado a importacao.
7. Cadastro de clientes.
8. Vendas com baixa de estoque.
9. Financeiro operacional basico: contas a receber, contas a pagar e fluxo de caixa derivado.
10. Dashboard resumido com indicadores operacionais principais.

Ficam fora do MVP:

1. Billing SaaS automatizado.
2. Integracao real com gateway de pagamento.
3. BI avancado e insights preditivos.
4. Mobile app.
5. IA.
6. Integracoes externas nao essenciais.
7. Relatorios gerenciais avancados e agendamentos complexos.

---

## 4. Modulos criticos do MVP

Sequencia oficial:

1. Fundacao tecnica.
2. Autenticacao e RBAC.
3. Tenant e configuracoes.
4. Importacoes.
5. Produtos e catalogo base.
6. Estoque.
7. Clientes.
8. Vendas.
9. Financeiro.
10. Dashboard resumido.

---

## 5. Regras definitivas para o MVP

- Toda entidade de negocio deve carregar `tenant_id`.
- Nenhuma consulta de modulo comercial pode existir sem filtro por tenant.
- Nao usar JWT na autenticacao web do MVP.
- O custo real do produto deve considerar custo de compra convertido + rateio das despesas da importacao.
- Toda baixa de estoque deve nascer de evento de venda, ajuste ou devolucao.
- Toda venda concluida deve produzir reflexo financeiro rastreavel.
- Todo fechamento de importacao deve congelar os valores usados no calculo daquela operacao, exceto mediante reprocessamento explicito.

---

## 6. Decisoes de UX do MVP

- Interface administrativa web responsiva.
- Navegacao principal lateral.
- Formularios server-rendered com enriquecimento progressivo.
- Dashboard inicial focado em indicadores de operacao, nao em analytics avancado.

---

## 7. Artefatos obrigatorios desta baseline

Para considerar a Sprint 01 concluida, o repositorio deve conter:

- composer.json
- .env.example
- bootstrap da aplicacao
- estrutura MVC inicial
- config de banco
- migracoes base
- rotas iniciais
- pagina inicial
- endpoint de health check
- suite minima de testes
