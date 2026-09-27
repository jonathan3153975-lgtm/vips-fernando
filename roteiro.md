# Roteiro de Tarefas — ImportControl

**Projeto:** ImportControl (vips-fernando)
**Data de criação:** 2026-09-27
**Fonte:** análise do estado atual do repositório + `manual/69 — Baseline única do MVP.md` + `manual/70 — Backlog técnico do MVP.md`
**Estado atual do código:** fundação técnica, autenticação/RBAC, importações (API) e leitura de produtos/estoque implementados. Vendas, financeiro e estoque transacional ainda não existem.

---

## 1. Como usar este roteiro

- Cada bloco é uma **etapa** com objetivo, tarefas numeradas e **critério de conclusão** objetivo.
- As etapas são sequenciais: não pule etapa. A Etapa 2 (higiene) destrava deploy confiável; a Etapa 5 (cadeia custo real) é o núcleo de valor do produto.
- Marcar `[x]` somente quando o **critério de conclusão** for verdadeiramente verificável.
- Toda tarefa que criar rota nova exige: item no OpenAPI (`docs/openapi-mvp.yaml`) + teste de integração em `tests/Integration/`.
- Toda consulta de negócio deve filtrar por `tenant_id` (regra 5 da baseline).

**Legenda de prioridade:** `P0` bloqueia deploy/produção · `P1` necessário para o MVP · `P2` evolui após o MVP

---

## ETAPA 0 — Estabelecer o diário de bordo

**Objetivo:** criar o registro de evolução que hoje não existe no repositório.

**Justificativa:** o `manual/` tem 70 documentos, mas nenhum registro contínuo de progresso. Os docs 68/69/70 refletem 01/08/2026 e já estão desatualizados frente ao código atual.

- [ ] `P1` Criar `manual/71 — Diário de Bordo.md` com a estrutura: data, objetivo da sessão, tarefas concluídas, decisões técnicas, pendências, bloqueios, testes executados.
  - [ ] `P1` Registrar no diário a linha de base de hoje (estado real: 6 testes verdes, 19 rotas, 30 tabelas, 11/20 endpoints do OpenAPI).
- [ ] `P1` Marcar `manual/68`, `manual/69` e `manual/70` como *snapshot histórico* (ou revisá-los contra o código atual).
- [ ] `P2` Marcar explicitamente na baseline 69 quais das ~8 séries duplicadas do `manual/` são canônicas e quais devem ser descartadas.

**Critério de conclusão:** existe `manual/71 — Diário de Bordo.md` com a primeira entrada datada e os docs 68/69/70 rotulados como snapshot.

---

## ETAPA 1 — Higiene técnica e confiabilidade

**Objetivo:** alinhar o repositório com a baseline e eliminar riscos de deploy quebrado.

**Bloqueio conhecido:** `.github/workflows/deploy.yml` instala **PHP 8.2**, mas `composer.json` exige **`^8.3`** → o `composer install` falha no deploy.

- [ ] `P0` Corrigir `php-version` do workflow para `8.3`.
- [x] `P0` Adicionar etapa de teste no CI antes do deploy, com falha bloqueando o `rsync` — job `quality` separado, o job `deploy` declara `needs: quality`.
- [x] `P0` Adicionar etapa de validação de sintaxe PHP — `tools/lint.php` sem dependências, exposto como `composer lint`.
- [x] `P0` Implementar proteção **CSRF** em todos os formulários com POST (`/login`, `/logout`).
  - Gerar token na sessão em `app/Core/Session.php` e validar em middleware novo `app/Middlewares/CsrfMiddleware.php`.
  - Incluir o campo hidden nos views `resources/views/auth/login.php` e `resources/views/dashboard/index.php`.
  - Registrar como middleware **global** no `Router`, para que nenhuma rota web nova fique desprotegida por esquecimento.
  - A API não exige token: ela aceita apenas `application/json`, o que impede requisição cross-origin sem preflight. Formato inválido retorna `415`.
- [x] `P1` Criar `README.md` com: pré-requisitos, instalação, `.env`, `composer migrate`, `composer seed`, `composer serve`, credenciais do seed, como rodar os testes.
- [~] `P1` Adicionar PHPStan (nível inicial) + regra PSR-12 e um script `composer lint` / `composer analyse`. — **parcial**: `phpstan.neon` criado e `composer lint` entregue, mas a instalação de `phpstan/phpstan` e `squizlabs/php_codesniffer` ficou bloqueada por indisponibilidade de rede no ambiente. Comando pronto: `composer require --dev phpstan/phpstan squizlabs/php_codesniffer`.
- [x] `P1` Ampliar `Router` com `delete()` e `patch()` (`app/Core/Router.php`).
- [x] `P1` Remover o `catch (\Throwable) {}` silencioso de `ProductRepository::ensureStock()` — substituído por `INSERT ... WHERE NOT EXISTS`, portátil entre MySQL/MariaDB e SQLite e sem esconder falha.
- [x] `P1` Implementar política de sessão e log: `APP_DEBUG` nunca pode ser `true` em produção; criar `storage/logs/` e um writer de log simples.
  - `config/app.php` força `debug = false` quando `APP_ENV=production`.
  - `app/Core/Logger.php` com níveis, mascaramento de segredos e `Logger::exception()` chamado em `Application::renderException()`.
  - Cookie de sessão com `secure`, `httponly` e `samesite` vindos da configuração; expiração por inatividade (`SESSION_IDLE_TIMEOUT`).
  - Cabeçalhos `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` e HSTS em produção.
  - `tests/bootstrap.php` direciona o log dos testes para a pasta temporária, evitando poluir o log da aplicação.
- [x] `P2` Criar `.editorconfig` e `.gitattributes`.
- [x] `P2` Adicionar health check de dependência: `GET /health` valida a conexão com o banco e retorna `503` com diagnóstico quando falha.

**Critério de conclusão:** `composer install` + `composer test` + `composer migrate` passam em PHP 8.3, o CI roda os testes antes do deploy, e nenhum formulário aceita POST sem token CSRF válido.

### Execução — 2026-09-27

**Arquivos alterados:** `.env.example`, `.github/workflows/deploy.yml`, `.gitignore`, `composer.json`, `composer.lock`, `phpunit.xml`, `config/app.php`, `bootstrap/app.php`, `routes/web.php`, `app/Core/*`, `app/Middlewares/CsrfMiddleware.php`, `app/Controllers/*`, `app/Repositories/ProductRepository.php`, `app/Services/HealthService.php`, `resources/views/*`, `tests/*`, `tools/lint.php`, `README.md`, `.editorconfig`, `.gitattributes`, `phpstan.neon`.

**Validação executada:**

| Verificação | Resultado |
|---|---|
| `composer lint` | 60 arquivos sem erro de sintaxe |
| `composer test` | **OK (16 testes, 74 asserções)** — era 6 testes / 36 asserções |
| `composer validate` | `composer.json` válido e `composer.lock` sincronizado |
| Smoke HTTP com `php -S` | ver tabela abaixo |

Cobertura de teste adicionada: `tests/Integration/CsrfTest.php` (6 casos) e `tests/Integration/HealthApiTest.php` (2 casos); `tests/HealthServiceTest.php` reescrito para os novos contratos de health check e de política de debug.

Smoke test end-to-end via HTTP real (`php -S`, banco SQLite):

| Cenário | Resultado |
|---|---|
| `GET /health` com banco acessível | `200` · `status: ok` · `checks.database.ok: true` |
| `GET /health` com banco inacessível | `503` · `status: degraded` com mensagem de diagnóstico |
| `GET /login` | `200` com campo `_token` renderizado |
| `POST /login` **sem** token | `302` — bloqueado pelo CSRF |
| `POST /login` **com** token | `302` para `/dashboard` — autenticado |
| `GET /dashboard` autenticado | `200` exibe usuário, tenant e logout com `_token` |
| `POST /logout` **sem** token | `302` — bloqueado |
| `POST /logout` **com** token | `302` para `/login` — sessão invalidada |
| `GET /dashboard` após logout | `302` para `/login` |
| `GET /api/v1/imports` sem sessão | `401` |
| `POST /api/v1/auth/login` form-encoded | `415` |

**Banco de dados local (resolvido):** criado com sucesso. `composer migrate` aplicou as 12 migrations (30 tabelas + a tabela de controle `migrations`) e `composer seed` populou o tenant demo, 5 perfis, 21 permissões e o usuário `admin@demo.local`. Verificado sem violações de integridade referencial e com smoke HTTP completo contra o MySQL real (`/health` 200 com `driver: mysql`, login com CSRF 302 → `/dashboard`, dashboard 200, logout 302 → `/login`).

Correção aplicada em `database/migrate.php`: o runner envolvia cada migration em `beginTransaction()`/`commit()`, o que é inválido em MySQL — DDL causa commit implícito, então o PDO perdia a transação e `commit()`/`rollBack()` lançavam `There is no active transaction`, matando o script na primeira migration.

Lacuna resolvida: as 30 declarações `CREATE TABLE` das migrations agora usam `IF NOT EXISTS`, alinhadas ao `DROP TABLE IF EXISTS` que o `down()` de cada migration já usava. Com isso, uma migration que falhou no meio pode ser reexecutada: as tabelas já criadas são ignoradas e as pendentes são concluídas. Validado removendo o registro da migration 12 em `migrations` e reexecutando `composer migrate` — as 5 tabelas preexistentes foram preservadas e a migration foi re-registrada. Sem a correção, a mesma operação falha com `SQLSTATE[42S01] 1050 Table 'financial_categories' already exists`.

Rollback e reset implementados. O `down()` existia completo (30 `DROP TABLE IF EXISTS`) mas era inalcançável: nenhum comando o invocava. Criado `App\Database\Migrator` em `database/Migrator.php` como motor compartilhado, com `up()`, `rollback()`, `reset()` e `fresh()`, e três entry points (`database/migrate.php`, `database/rollback.php`, `database/fresh.php`) expostos como `composer migrate`, `composer migrate:rollback` e `composer migrate:fresh`. O `rollback` reverte um batch inteiro em ordem inversa; `migrate:fresh` exige `--force` em produção. Validado contra o MySQL real: rollback do batch 2 reverteu 11 migrations na ordem inversa e reduziu o banco a 2 tabelas; `migrate` restaurou; `fresh` reverteu as 12 e reaplicou as 12 em ordem direta, deixando tudo em batch 1; o guard de produção bloqueou a execução sem `--force` (exit 1) sem tocar no banco.

Observação residual: `IF NOT EXISTS` torna a reexecução segura, mas não detecta divergência de schema. Se uma migration for editada depois de aplicada, a tabela existente não é alterada. A partir do momento em que houver migration em produção, o fluxo tem que ser "sempre nova migration", nunca editar as antigas.

**Lacuna remanescente:** instalar `phpstan/phpstan` e `squizlabs/php_codesniffer` (bloqueado por rede). O `phpstan.neon` já está no repositório; basta rodar o comando indicado e adicionar `"analyse": "phpstan analyse"` ao `composer.json`.

---

## ETAPA 2 — Fechar autenticação, RBAC e multi-tenant (EPIC 02 + EPIC 03)

**Objetivo:** completar o controle de acesso e fazer o isolamento multi-tenant ser uma garantia da arquitetura, não uma disciplina manual.

### 2.1 Gestão de usuários, perfis e permissões (EPIC 02)
- [ ] `P1` `UserRepository`: listar, buscar, criar, atualizar, bloquear/desativar — sempre com `tenant_id`.
- [ ] `P1` `UserService`: validação de e-mail único por tenant, hash de senha, impossível bloquear o último admin ativo.
- [ ] `P1` `RoleService`: CRUD de perfis e de suas permissões (`role_permissions`).
- [ ] `P1` API: `GET/POST /api/v1/users`, `GET/PUT /api/v1/users/{id}`, `GET/POST /api/v1/roles`, `PUT /api/v1/roles/{id}/permissions`.
- [ ] `P1` Telas server-rendered de usuários e perfis, com menu lateral.
- [ ] `P1` Aplicar as permissões já semeadas e hoje sem uso: `users.view`, `users.manage`, `stock.adjust`, `customers.*`, `sales.*`, `financial.*`.
- [ ] `P2` Rate limit de tentativas de login e bloqueio progressivo.
- [ ] `P2` Política de senha forte e 2FA (fora do escopo do MVP, já previsto na baseline).

### 2.2 Recuperação de senha (EPIC 02 — fluxo hoje incompleto)
> Hoje `PasswordResetService::request()` gera o token, mas **nada é enviado e não existe rota de redefinição**. O fluxo morre no passo 1.

- [ ] `P0` Criar `POST /api/v1/auth/password-reset/confirm` com `token` + `password`, validando expiração (1 h) e `used_at`.
- [ ] `P1` Invalidar todas as sessões do usuário após a redefinição.
- [ ] `P1` Telas: "esqueci minha senha" e "definir nova senha".
- [ ] `P2` Envio de e-mail (ver `manual/20 — Integrações Externas`) com template e link assinada.

### 2.3 Tenant e configurações (EPIC 03)
> Hoje `tenant_settings` é criada e populada pelo seed, mas **nunca é lida pelo código**.

- [ ] `P1` `TenantRepository` + `TenantService`: cadastro, leitura e atualização de `tenant_settings`.
- [ ] `P1` `TenantContext`: resolver `tenant_id` ativo na sessão e expor moeda, timezone e formato de data para as views.
- [ ] `P1` Fazer `APP_TIMEZONE` e o formato de data refletirem o tenant, não o global.
- [ ] `P1` Exibir moeda do tenant nos campos de valor e nos cards de indicadores.
- [ ] `P1` Tela de configurações do tenant.
- [ ] `P2` Troca de tenant para usuário com acesso a mais de uma empresa.
- [ ] `P2` Painel administrativo do SaaS (criar/empresa, suspender assinatura, limites) — `manual/19`.

### 2.4 Garantia estrutural de isolamento (prioridade máxima de segurança)
- [x] `P1` Introduzir uma camada base de repositório que **exija** `tenant_id` em toda consulta de negócio, tornando o vazamento de dados impossível por omissão.
- [x] `P1` Adicionar testes de integração que tentem, explicitamente, acessar registro de outro tenant em **todas** as rotas por id e esperar 404/403.
- [x] `P2` Migrar os repositories existentes para a nova camada.

**Implementado:**
- `BaseRepository` (acesso a `PDO`) e `TenantScopedRepository` (exige o marcador `:tenant_id` e injeta o tenant da sessão; lança `UnscopedQueryException` se faltar, e `MissingTenantException` se não houver tenant ativo). A validação roda **antes** de preparar a query, para não depender do banco estar acessível.
- `TenantContext` resolve o tenant e o usuário a partir da sessão, de forma lazy (repositories são construídos antes do login).
- `NotFoundException` substitui a comparação de string que decidia o `404` nos controllers.
- Migrados: `UserRepository`, `ProductRepository`, `ImportRepository`, `AuditLogRepository`. `tenantId`/`userId` saíram das assinaturas de services e controllers.
- Vazamentos latentes corrigidos: `UserRepository::findById()` e `permissionsForUser()` agora filtram por tenant; `expenseTotal()`, `itemTotals()` e `allocateExpenses()` passaram a filtrar por tenant e a validar a posse do registro (`findOrFail`).
- Filas sem `tenant_id` própria (`product_prices`) são escopadas via subquery no pai (`products`), para funcionar em MySQL e SQLite.
- **Exceções deliberadas e documentadas** (fora do escopo, pois ainda não existe tenant na sessão): `UserRepository::findByEmail()` (login é o único ponto em que o tenant é desconhecido) e `AuditLogRepository::createForTenant()`, usada só por `PasswordResetService`.
- `AuthService::attempt()` passou a gravar a identidade na sessão **antes** de carregar as permissões (a leitura virou tenant-scoped), com rollback da sessão se a carga falhar.
- `tests/Integration/CrossTenantTest.php`: 11 testes cobrindo as 6 rotas por id, listagem, `findById`/`permissionsForUser` entre tenants, e as duas guardas. Total da suíte: 27 testes / 117 asserções.
- Validado também no MySQL real: isolamento entre tenants, `INSERT..SELECT` em `product_prices` e fluxo de login.

**Limitações conhecidas:**
- A guarda exige o marcador `:tenant_id`, mas é uma convenção de runtime: não prova que o predicado está semanticamente correto (ex.: `p.tenant_id` pode estar na tabela errada, via `JOIN`).
- `pdo()` é `protected`, então uma classe pode contornar a guarda de propósito. Hoje nenhuma faz.
- Tabelas sem `tenant_id` dependem do pai estar corretamente filtrado; não há verificação automática disso.
- `products.category_id` / `brand_id` / `supplier_id` e `imports.responsible_user_id` aceitam hoje qualquer `id` existente, inclusive de outro tenant. Tratar na Etapa 2.1/2.2.

**Critério de conclusão:** um usuário sem permissão recebe 403 consistente; um usuário do tenant A que tenta acessar registro do tenant B recebe 404 em qualquer endpoint por id; fluxo completo de recuperação de senha funciona ponta a ponta; configurações do tenant influenciam a exibição.

---

## ETAPA 3 — Corrigir e completar o núcleo de custo (EPIC 04)

**Objetivo:** tornar o cálculo de custo real confiável. Este é o núcleo do produto — se esta etapa ficar errada, todo o resto herda o erro.

> Regras da baseline 69 §5 que **ainda não são implementadas**: congelamento de valores no fechamento, rateio além de valor, residuo de centavos.

- [ ] `P0` Envolver `ImportService::complete()` em **transação** — hoje uma falha no meio deixa a importação parcialmente calculada.
- [ ] `P0` Tratar **resíduo de arredondamento**: distribuir a diferença entre soma dos rateios e `total_expenses` no último item, garantindo soma exata em centavos.
- [ ] `P1` Congelar valores no fechamento: gravar taxa, despesas e rateio aplicados; edição posterior exige **reprocessamento explícito** e auditado.
- [ ] `P1` Definir e implementar a fórmula de rateio por peso, por quantidade e por combinação, com a regra padrão documentada.
- [ ] `P1` Regras de transição de estado: quais status permitem edição, fechamento, reabertura; bloquear edição de importação `COMPLETED`.
- [ ] `P1` `ExchangeRateService` + API para cotação manual (`exchange_rates` existe e nunca é usada): `GET/POST /api/v1/exchange-rates`.
- [ ] `P1` Ao lançar despesa/item, sugerir a taxa vigente na data a partir de `exchange_rates` em vez de exigir digitação.
- [ ] `P1` CRUD de fornecedores (`suppliers` existe, sem código): `GET/POST/PUT /api/v1/suppliers`.
- [ ] `P1` API de listagem de despesas e itens da importação (`GET /api/v1/imports/{id}/expenses|items`), com paginação e filtros.
- [ ] `P2` Reprocessamento completo de uma importação com diff do que mudou.
- [ ] `P2` Anexos e comprovantes de despesa (storage — `manual/20`).
- [ ] `P2` Suíte de testes de casos de borda do rateio: 0 despesas, 0 itens, 1 item, divisão não exata, valor negativo, múltiplas moedas.

**Critério de conclusão:** fechar uma importação com 3 itens e despesas de valor não divisível gera soma de rateios exatamente igual ao total, dentro de transação, e os valores ficam congelados; testes automatizados cobrem os casos de borda.

---

## ETAPA 4 — Catálogo, produtos e preços (EPIC 05)

**Objetivo:** estruturar o que é comercializado e formar preço de venda a partir do custo real.

- [ ] `P1` CRUD de categorias (`GET/POST/PUT /api/v1/categories`) e marcas (`/api/v1/brands`).
- [ ] `P1` Preenchimento automático de `default_import_item_id` ao criar produto a partir de um item de importação.
- [ ] `P1` `PricingService`: calcular preço sugerido a partir do custo real (vindo do rateio da importação) + margem, gravando `cost_price`, `sale_price`, `minimum_price` e `margin` em `product_prices`.
- [ ] `P1` Na criação/edição de produto, defaulted `cost_price` com o custo real quando o produto está vinculado a um item de importação.
- [ ] `P1` Listagem com filtros (categoria, marca, status, busca textual) e paginação padronizada.
- [ ] `P1` Bloquear exclusão de produto com estoque ou histórico de venda; desativar em vez de excluir.
- [ ] `P1` Tela de listagem e formulário de produto com categorias/marcas em selects.
- [ ] `P2` Cálculo de margem efetiva e alerta de preço abaixo do mínimo.
- [ ] `P2` Importação/exportação de catálogo em CSV/Excel.
- [ ] `P2` Código de barras e leitura por scanner.

**Critério de conclusão:** produto vinculado a item de importação recebe `cost_price` igual ao custo real rateado; produto com histórico não pode ser excluído; listagem filtra e pagina.

---

## ETAPA 5 — Estoque transacional e rastreabilidade (EPIC 06)

**Objetivo:** fechar a cadeia custo real → produto → estoque. **Sem esta etapa o MVP não entrega valor.**

> Hoje existe apenas `GET /api/v1/products/{id}/stock` (leitura de saldo). `stock_movements` foi criada e nunca usada.

- [ ] `P0` `StockService::receiveFromImport()`: ao concluir uma importação, gerar **lotes** e dar entrada no estoque de cada produto vinculado, gravando `stock_movements` do tipo `IMPORT_ENTRY`.
- [ ] `P0` Criar tabela de **lotes** (`product_lots`) com `import_id`, custo unitário congelado, data de entrada e quantidade.
- [ ] `P1` `StockService::adjust()` para ajuste manual controlado, exigindo permissão `stock.adjust` e justificativa obrigatória, gravando movimentação.
- [ ] `P1` Consulta de saldo por produto **e por lote** (`GET /api/v1/stock?product_id=&lot_id=`).
- [ ] `P1` Histórico de movimentações (`GET /api/v1/stock/{id}/movements`) com filtros por tipo, período e usuário.
- [ ] `P1` Bloquear saldo negativo; definir a regra explícita (permitir com alçada ou proibir) e documentá-la.
- [ ] `P1` `reserved_quantity`: reserva de estoque para venda e liberação em caso de cancelamento.
- [ ] `P1` Alerta de estoque abaixo do mínimo (`minimum_quantity`).
- [ ] `P1` Tela de estoque com saldo por produto/lote e formulário de ajuste.
- [ ] `P2` Inventário periódico com contagem e divergência.
- [ ] `P2` Transferência entre unidades de negócio do mesmo tenant.

**Critério de conclusão:** concluir uma importação com produto vinculado gera lote, entrada em estoque e movimentação auditável; ajuste manual sem permissão é bloqueado; saldo nunca fica negativo.

---

## ETAPA 6 — Clientes e CRM (EPIC 07)

**Objetivo:** centralizar dados comerciais e histórico de relacionamento.

- [ ] `P1` `CustomerRepository` + `CustomerService`: cadastro, busca por nome/documento/contato.
- [ ] `P1` API: `GET/POST /api/v1/customers`, `GET/PUT /api/v1/customers/{id}`, com filtros e paginação.
- [ ] `P1` Telas: listagem com busca/filtros e formulário de cadastro.
- [ ] `P1` Prevenir duplicidade: bloquear documento já existente no mesmo tenant.
- [ ] `P1` Histórico comercial do cliente: vendas vinculadas, valor total, última compra.
- [ ] `P1` Soft delete (bloqueio) em vez de exclusão quando houver histórico.
- [ ] `P2` Fichas, anotações e timeline de interação.
- [ ] `P2` Segmentação, etiqueta e lista de aniversário/ano-versário.

**Critério de conclusão:** cliente cadastrado aparece na busca por qualquer critério, não duplica por documento, e o histórico lista as vendas do próprio tenant.

---

## ETAPA 7 — Vendas e checkout (EPIC 08)

**Objetivo:** converter estoque em receita, com reflexo consistente em estoque e financeiro.

> As 6 tabelas (`sales`, `sale_items`, `sale_discounts`, `payments`, `sale_returns`) existem e **nenhuma das rotas do OpenAPI está implementada**.

- [ ] `P0` `POST /api/v1/sales` — cria venda `OPEN`, valida estoque disponível, reserva quantidade, calcula subtotal/desconto/total/custo/lucro.
- [ ] `P0` `POST /api/v1/sales/{id}/items` — adiciona item, valida permissão e estoque.
- [ ] `P0` `POST /api/v1/sales/{id}/complete` — baixa estoque definitively, grava `stock_movements` do tipo `SALE_OUT`, gera contas a receber e concilia pagamento.
- [ ] `P0` `POST /api/v1/sales/{id}/cancel` — reverte reserva/baixa e libera estoque; **exige** permissão `sales.cancel`.
- [ ] `P0` `POST /api/v1/sales/{id}/return` — devolução parcial ou total com estorno proporcional e reentrada em estoque.
- [ ] `P1` Número sequencial de venda por tenant (`sale_number` único).
- [ ] `P1` Alteração de preço livre: exigir permissão `sales.change_price` e **registrar o preço original** em `sale_discounts`/auditoria.
- [ ] `P1` Desconto controlado: exigir `sales.discount` acima de um limite percentual definido no tenant.
- [ ] `P1` Pagamentos: registrar método, valor, parcelas e status; permitir pagamento parcial e misto.
- [ ] `P1` Telas: carrinho, checkout, lista de vendas, detalhe com baixa/estorno/devolução.
- [ ] `P1` Fechar venda em **transação** com rollback completo em qualquer falha.
- [ ] `P2` Orçamentos e reserva de cliente.
- [ ] `P2` Nota fiscal / integração com SEFAZ (fora do MVP).

**Critério de conclusão:** venda concluída baixa estoque, gera movimentação e lançamento financeiro rastreável; cancelamento e devolução reversionam de forma consistente; usuário sem permissão não altera preço.

---

## ETAPA 8 — Financeiro e fluxo de caixa (EPIC 09)

**Objetivo:** refletir em contas a pagar e a receber os eventos do negócio.

> 5 tabelas prontas; 4 endpoints do OpenAPI pendentes.

- [ ] `P0` `POST /api/v1/financial` — lançamentos de receita e despesa com categoria (`financial_categories`).
- [ ] `P0` `GET /api/v1/accounts-receivable` e `GET /api/v1/accounts-payable` — com saldo, vencimento e status.
- [ ] `P0` `POST /api/v1/accounts-receivable/{id}/settle` e `POST /api/v1/accounts-payable/{id}/settle` — baixa manual e conciliação.
- [ ] `P0` `GET /api/v1/cash-flow` — entradas, saídas e saldo por período.
- [ ] `P1` Regras de status: `PENDENTE`, `PAGO`, `VENCIDO`, `PARCIAL`; cálculo de atraso.
- [ ] `P1` Venda gera contas a receber automaticamente; importação e suas despesas geram contas a pagar.
- [ ] `P1` Visão resumida de lucro operacional: receita − custo − despesas do período.
- [ ] `P1` Telas: financeiro, contas a receber, contas a pagar, fluxo de caixa.
- [ ] `P1` Corrigir/recategorizar lançamento mediante permissão e trilha de auditoria.
- [ ] `P2` Conciliação bancária por extrato.
- [ ] `P2` DRE simples e relatório de margem por produto/importação.
- [ ] `P2` Exportação PDF/Excel (`manual/37`).

**Critério de conclusão:** despesa relevante de importação impacta o financeiro; venda impacta contas a receber; fluxo de caixa exibe entradas e saídas por período; lucro operacional bate com os módulos operacionais.

---

## ETAPA 9 — Dashboard e indicadores (EPIC 10)

**Objetivo:** substituir a tela estática por indicadores reais do MVP.

> Hoje `resources/views/dashboard/index.php` exibe apenas tenant, perfil e contagem de permissões. **Nenhum indicador real.**

- [ ] `P1` `DashboardService` agregador, com filtro obrigatório por `tenant_id`.
- [ ] `P1` Card: vendas do período (quantidade + faturamento) vs. período anterior.
- [ ] `P1` Card: estoque atual em valor e em itens.
- [ ] `P1` Card: contas a receber e a pagar (total e vencidas).
- [ ] `P1` Card: lucro operacional do período.
- [ ] `P1` Card: importações em andamento (com total investido).
- [ ] `P1` Gráficos: vendas por período,-top produtos, top clientes.
- [ ] `P1` Filtro de período e conversão de moeda respeitando `tenant_settings`.
- [ ] `P1` Estados de loading, vazio e erro de cada card.
- [ ] `P1` **Adotar Bootstrap 5 de fato** — hoje as views usam CSS inline e contrariam a baseline 69 §2.
- [ ] `P1` Layout com **navegação principal lateral** (exigido pela baseline 69 §6) e menu reflecting as permissões do usuário.
- [ ] `P2` Mais KPIs: ticket médio, margem média, giro de estoque, produtos parados, ranking de clientes.
- [ ] `P2` Alertas automáticos: produto sem estoque, conta vencendo, cliente inadimplente.

**Critério de conclusão:** o dashboard carrega dados reais do tenant logado, os indicadores batem com os módulos operacionais e a navegação lateral esconde itens sem permissão.

---

## ETAPA 10 — Fechamento técnico e entrega (Sprint 20 do plano)

- [ ] `P1` Revisão de segurança: OWASP top 10, sessão, cookies, cabeçalhos (`X-Frame-Options`, `CSP`, `HSTS`), rate limit.
- [ ] `P1` Testes de cobertura dos cálculos financeiros e de estoque como prioritise — são o núcleo do produto.
- [ ] `P1` Checklist de release do `manual/28` §12 validado: login, permissões, cadastro, cálculos, estoque, financeiro, segurança, backup.
- [ ] `P1` Estratégia de backup e restauração testada (`database/backups/`).
- [ ] `P1` Ambiente de homologação separado de produção.
- [ ] `P1` Política de conformidade LGPD: consentimento, retenção, mascaramento de dados sensíveis em log.
- [ ] `P2` Monitoramento e alertas de erro em produção.
- [ ] `P2` Otimização de performance e índices para as consultas críticas.
- [ ] `P2` Code review e.padronização de commits (`feat:`, `fix:`, `refactor:`, `security:`).
- [ ] `P2` Branch convention real: `main` / `develop` / `feature/*`.

**Critério de conclusão:** o MVP opera em produção com backup testado, checklist de release assinado e sem vulnerabilidade crítica aberta.

---

## Resumo visual

```
ETAPA 0  Diário de bordo                 ░░░░░░░░░░  (~0,5 dia)
ETAPA 1  Higiene e CI                    ▓▓▓░░░░░░░  (~2 dias)   P0
ETAPA 2  Auth + RBAC + Tenant            ▓▓▓▓▓░░░░░  (~5 dias)   MVP
ETAPA 3  Núcleo de custo (rateio)        ▓▓▓▓▓▓░░░░  (~5 dias)   ★ núcleo do produto
ETAPA 4  Produtos e preços               ▓▓▓░░░░░░░  (~3 dias)   MVP
ETAPA 5  Estoque transacional            ▓▓▓▓▓░░░░░  (~5 dias)   MVP ★
ETAPA 6  Clientes e CRM                  ▓▓▓░░░░░░░  (~3 dias)   MVP
ETAPA 7  Vendas e checkout               ▓▓▓▓▓▓▓░░░  (~7 dias)   MVP ★
ETAPA 8  Financeiro e caixa              ▓▓▓▓▓▓░░░░  (~5 dias)   MVP
ETAPA 9  Dashboard e UX                  ▓▓▓▓░░░░░░  (~4 dias)   MVP
ETAPA 10 Fechamento e produção           ▓▓░░░░░░░░  (~3 dias)
```

★ = etapas de maior risco e maior valor de negócio.

**Sequência crítica de valor:** Etapa 3 → 5 → 7 → 8. É por ela que o usuário descobre o **custo real** e o **lucro** — o motivo do sistema existir.

---

## Checklist rápido por rota nova

Antes de considerar qualquer tarefa de API concluída:

- [ ] `docs/openapi-mvp.yaml` atualizado com request, response, códigos HTTP e exemplo
- [ ] Todas as consultas filtram por `tenant_id`
- [ ] Validação de entrada no Service (não no Controller)
- [ ] Resposta de erro no padrão do projeto (`{"message": "..."}`)
- [ ] Teste de integração em `tests/Integration/` cobrindo caminho feliz, permissão negada e tenant diferente
- [ ] Regra de negócio ou cálculo financeiro tem teste de caso de borda
- [ ] Entrada no diário de bordo (ETAPA 0)
