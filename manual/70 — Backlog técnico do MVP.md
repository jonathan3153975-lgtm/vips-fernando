# Backlog Tecnico do MVP

**Projeto:** ImportControl  
**Data:** 2026-08-01  
**Escopo:** backlog tecnico priorizado a partir da baseline canonica do MVP

---

## 1. Regras de priorizacao

- Primeiro, fundacao tecnica e isolamento multi-tenant.
- Depois, autenticacao e controle de acesso.
- Em seguida, cadeia operacional que gera custo e receita.
- Por fim, consolidacao financeira e visao gerencial resumida.

---

## 2. Backlog priorizado

### EPIC 01 - Fundacao tecnica

**Objetivo:** colocar o projeto em estado executavel com arquitetura base e padroes fixados.

**Itens:**

1. Bootstrap da aplicacao e autoload PSR-4.
2. Configuracao por ambiente com `.env`.
3. Router HTTP inicial e tratamento de erros.
4. Conexao PDO centralizada.
5. Estrutura de migrations e seeders.
6. Health check e teste de fumaca.

**Criterios de aceite:**

- `composer install` executa sem falha.
- `composer test` executa pelo menos um teste verde.
- `composer migrate` encontra o runner e tenta aplicar migrations.
- A rota `/health` responde JSON valido.

---

### EPIC 02 - Autenticacao e RBAC

**Objetivo:** garantir acesso seguro e separado por tenant.

**Itens:**

1. Tela de login.
2. Sessao segura com expiracao e logout.
3. Cadastro de usuarios.
4. Cadastro de perfis e permissoes.
5. Middleware de autenticacao.
6. Middleware de autorizacao por permissao.
7. Recuperacao de senha por fluxo controlado.

**Criterios de aceite:**

- Usuario ativo autenticado acessa apenas o proprio tenant.
- Usuario sem permissao recebe bloqueio consistente.
- Logout invalida a sessao atual.
- Senha e armazenada apenas em hash seguro.

**Dependencias:** EPIC 01.

---

### EPIC 03 - Tenant e configuracoes basicas

**Objetivo:** preparar a aplicacao para operacao multiempresa desde o inicio.

**Itens:**

1. Cadastro de tenant.
2. Configuracoes basicas de moeda, timezone e formato de data.
3. Resolucao do tenant no contexto da sessao.
4. Filtro obrigatorio por `tenant_id` nas consultas.

**Criterios de aceite:**

- Cada usuario e vinculado a um tenant.
- Nenhum dado comercial e listado fora do tenant ativo.
- Configuracoes do tenant influenciam exibicao basica do sistema.

**Dependencias:** EPIC 02.

---

### EPIC 04 - Importacoes e viagens

**Objetivo:** registrar a operacao de compra internacional que gera o custo-base do negocio.

**Itens:**

1. Cadastro de importacao/viagem.
2. Cadastro de despesas da importacao.
3. Cadastro de cotacao manual.
4. Cadastro de compras e itens adquiridos.
5. Fechamento da importacao.

**Criterios de aceite:**

- Importacao possui status controlado.
- Despesas ficam vinculadas a importacao correta.
- Cotacao usada no fechamento fica registrada.
- Fechamento gera base para rateio e estoque.

**Dependencias:** EPIC 03.

---

### EPIC 05 - Produtos e catalogo base

**Objetivo:** estruturar os itens comercializados com identificacao minima e rastreabilidade por lote.

**Itens:**

1. Cadastro de produto.
2. Categorias e marcas basicas.
3. Vinculo do item comprado ao produto do catalogo.
4. Campos de custo e preco sugerido.

**Criterios de aceite:**

- Produto pode ser identificado unicamente por tenant.
- Produto pode receber historico de entrada por lote.
- Preco sugerido pode ser calculado a partir do custo.

**Dependencias:** EPIC 04.

---

### EPIC 06 - Estoque por lote

**Objetivo:** controlar entrada, saldo e movimentacao com rastreabilidade financeira.

**Itens:**

1. Geracao de lotes a partir da importacao.
2. Entrada de estoque.
3. Ajustes manuais controlados.
4. Historico de movimentacoes.
5. Consulta de saldo por produto e lote.

**Criterios de aceite:**

- Cada entrada gera lote vinculado a importacao.
- Saldo nao pode ficar negativo sem regra explicita.
- Toda movimentacao e auditavel.

**Dependencias:** EPIC 05.

---

### EPIC 07 - Clientes

**Objetivo:** centralizar dados comerciais e historico de relacionamento.

**Itens:**

1. Cadastro de cliente.
2. Busca e filtro por nome, documento e contato.
3. Historico comercial basico.

**Criterios de aceite:**

- Cliente pode ser usado na venda sem duplicidade evidente.
- Historico mostra vendas vinculadas ao cliente.

**Dependencias:** EPIC 02.

---

### EPIC 08 - Vendas e checkout operacional

**Objetivo:** converter estoque em receita com reflexo consistente em estoque e financeiro.

**Itens:**

1. Criacao de venda.
2. Adicao de itens e quantidades.
3. Aplicacao de desconto controlado.
4. Escolha de forma de pagamento.
5. Finalizacao de venda.
6. Cancelamento e devolucao inicial.

**Criterios de aceite:**

- Venda concluida baixa estoque.
- Venda concluida gera lancamento financeiro rastreavel.
- Cancelamento ou devolucao executa reversoes permitidas.
- Usuario sem permissao nao altera preco livremente.

**Dependencias:** EPIC 06 e EPIC 07.

---

### EPIC 09 - Financeiro operacional

**Objetivo:** refletir contas a pagar e receber originadas dos eventos do negocio.

**Itens:**

1. Contas a pagar da importacao e despesas.
2. Contas a receber de vendas.
3. Fluxo de caixa consolidado.
4. Baixa manual e conciliacao inicial.
5. Visao resumida de lucro operacional.

**Criterios de aceite:**

- Despesa relevante impacta financeiro.
- Venda impacta contas a receber.
- Fluxo de caixa exibe entradas e saidas por periodo.

**Dependencias:** EPIC 04 e EPIC 08.

---

### EPIC 10 - Dashboard resumido

**Objetivo:** expor os indicadores essenciais do MVP.

**Itens:**

1. Vendas do periodo.
2. Estoque atual.
3. Contas a receber e a pagar.
4. Lucro operacional resumido.
5. Importacoes em andamento.

**Criterios de aceite:**

- Dashboard carrega dados reais do tenant logado.
- Indicadores batem com os modulos operacionais.
- Performance aceitavel para uso diario do MVP.

**Dependencias:** EPIC 09.

---

## 3. Ordem de implementacao por sprint

1. Sprint 01: EPIC 01.
2. Sprint 02: EPIC 02 e EPIC 03.
3. Sprint 03: EPIC 04.
4. Sprint 04: EPIC 05 e EPIC 06.
5. Sprint 05: EPIC 07 e EPIC 08.
6. Sprint 06: EPIC 09.
7. Sprint 07: EPIC 10.

---

## 4. Definicoes que devem virar tarefas tecnicas em seguida

1. Contrato OpenAPI do modulo de autenticacao.
2. Modelagem final de importacoes, produtos, estoque, vendas e financeiro.
3. Regras matematicas de rateio e lucro com casos de teste.
4. Wireframes das telas criticas do MVP.
