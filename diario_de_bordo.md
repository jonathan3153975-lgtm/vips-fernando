# Diário de Bordo — ImportControl

Registro contínuo de evolução do projeto. Complementa o `roteiro.md` (que é o
plano de tarefas) e o `manual/` (que é a especificação). Aqui fica o que
**aconteceu de fato**, na ordem, com as decisões tomadas e o estado real do
código.

- **Última atualização:** 2026-10-02
- **Fonte do plano:** `roteiro.md`
- **Documento pedido pela Etapa 0** como `manual/71 — Diário de Bordo.md`; está
  aqui na raiz com o nome `diario_de_bordo.md`.

## Como usar

- Cada sessão de trabalho adiciona uma entrada em **Histórico** com data,
  objetivo, o que foi feito, decisões, testes executados e pendências.
- O bloco **Onde parou** é sempre o mais atual e deve ser lido primeiro.
- Regras de negócio, schema e fórmulas continuam no `manual/`; aqui só se
  registra o que foi implementado e o que ficou em aberto.

## Legenda

- `[x]` concluído e validado · `[~]` parcial · `[ ]` pendente · `BLOQUEIO` impedido por algo externo
- **Validado** significa: suíte de testes verde, `composer lint` limpo e, quando
  toca banco/SQL, verificação no MySQL real e/ou smoke HTTP com `php -S`.

---

## Linha de base inicial

Estado registrado no `roteiro.md` (Etapa 0), anterior a este trabalho:

- 6 testes verdes, 19 rotas, 30 tabelas, 11/20 endpoints do OpenAPI.
- Nenhum diário de bordo no repositório; docs `manual/68`, `manual/69` e
  `manual/70` datados de 2026-08-01 e já defasados frente ao código.
- Commits iniciais: `3111ae5 Create deploy.yml` (2026-08-01), `2fb59de inicial`.

---

## Histórico

### Etapa 1 — Higiene técnica e confiabilidade
**Commit:** `ba91e36` (2026-09-27)

- Migrations, seed e rollback funcionando (`create-database.sql`,
  `database/migrate.php`, `fresh.php`, `rollback.php`).
- `composer lint` (sintaxe), `composer test` (PHPUnit 11) e health check.
- Estado verde ao final: 27 testes / 117 asserções.
- **Bloqueio que permanece:** PHPStan e PHPCS não instaláveis na ocasião
  (Packagist indisponível). Também não há PHP 8.3 local, só 8.4.
- CI (GitHub Actions) validado apenas estaticamente; nunca rodou em push real.

### Etapa 2 — Autenticação, RBAC e multi-tenant
**Commits:** `69b241d`, `eb0d651`, `099c7b5`, `e39f066` (2026-09-27)

Executada fora da ordem do roteiro: **2.4 → 2.1 → 2.3 → 2.2**.

#### 2.4 — Garantia estrutural de isolamento (primeiro, por ser o mais crítico)
- `BaseRepository`, `TenantScopedRepository` (exige o marcador `:tenant_id` e
  injeta o tenant da sessão), `TenantContext`, `MissingTenantException`,
  `UnscopedQueryException`, `NotFoundException`.
- Migrados `UserRepository`, `ProductRepository`, `ImportRepository`,
  `AuditLogRepository`. `tenantId`/`userId` saíram das assinaturas de
  services/controllers.
- Exceções pré-auth deliberadas: `findByEmail` (login) e `createForTenant`
  (reset de senha).
- `CrossTenantTest.php` (11 testes). Validado no MySQL real (19/19) e smoke HTTP.
- Limitação registrada: a guarda é de runtime, não prova que o predicado está
  semanticamente correto.

#### 2.1 — Gestão de usuários, perfis e permissões
- `UserRepository`, `RoleRepository`, `PermissionRepository`, `UserService`,
  `RoleService`, APIs e telas (`/usuarios`, `/perfis`).
- Invariante do último administrador ativo; perfis de sistema; unicidade de nome
  por tenant.
- **Três bugs reais encontrados só na validação de ponta a ponta:**
  1. `Request::all()` descartava **todo** formulário web: a condição
     `content-type JSON || corpo não vazio` fazia o form nativo cair no
     `json_decode`, falhar e devolver `[]`. No CLI `php://input` é vazio, então a
     suíte passava por um caminho que o servidor real nunca percorre.
  2. Formulários nativos postavam em `/api/`, que responde 415 a
     `x-www-form-urlencoded`. Criadas rotas web com PRG.
  3. `RoleService::create()` validava permissões **depois** de criar o perfil,
     deixando perfil órfão. Validação movida para antes da escrita e
     `replacePermissions()` para transação.
- Desvio do roteiro: `users.email` mantido **único global** (não por tenant),
  porque o login resolve o tenant a partir do e-mail.
- Estado verde: 85 testes / 338 asserções.

#### 2.3 — Tenant e configurações
- Migration `000013`: `UNIQUE` em `tenants.document` e `tenants.email`. O `up()`
  não apaga duplicados — falha listando, para decisão humana. Com o índice, o
  seed virou idempotente.
- `TenantRepository`, `TenantService`, `TenantFormatter` e tela `/configuracoes`.
- `TenantContext` passou a expor moeda, fuso, idioma e formato de data (via
  sessão, carregados no login).
- **Decisão registrada:** o fuso do tenant é aplicado **na exibição**, não em
  `date_default_timezone_set()`. Gravações usam `date()` em colunas
  `DATETIME`/`TIMESTAMP`; mudar o fuso global no meio do request deslocaria
  timestamps já gravados.
- Estado verde: 101 testes / 394 asserções.

#### 2.2 — Recuperação de senha
- `POST /api/v1/auth/password-reset/confirm` + telas `/esqueci-senha` e
  `/redefinir-senha`.
- **Token nunca gravado em claro** (só `sha256`); pedir novo link invalida o
  anterior.
- **Invalidação de sessões via `auth_version`** (migration `000014`): o login
  grava a versão, `AuthService::check()` compara com o banco e derruba a sessão
  quando a senha muda. Sem isso não havia como invalidar sessões, que ficam em
  arquivo no servidor sem índice por usuário.
- Regra de senha extraída para `App\Support\PasswordPolicy`.
- **P2 pendente:** envio de e-mail. Variáveis reservadas no `.env`/`.env.example`
  (`MAIL_*`), sem integração. Em `APP_DEBUG=true` a tela mostra o link gerado
  para permitir teste manual.
- Estado verde: 121 testes / 458 asserções.

### Etapa 3 — Núcleo de custo (EPIC 04)
**Commit:** `5e38611` (2026-09-27)

- **P0 transação:** `ImportRepository::freeze()` faz o cálculo inteiro (ler itens,
  somar despesas, gravar rateio e totais) em uma única transação.
- **P0 resíduo de centavos:** rateio calculado em centavos inteiros; o último
  item absorve a diferença, então a soma dos rateios é exatamente
  `total_expenses` (ex.: R$100 em 3 itens → 33,33 / 33,33 / 33,34).
- **Congelamento:** grava `allocation_method`, `completed_at` e os valores
  aplicados. Depois de `COMPLETED`, `update`/`addExpense`/`addItem`/novo
  `complete` retornam 400. `POST /imports/{id}/reopen` reabre de forma explícita
  e auditada.
- **Status canônicos:** `PLANNED` (default), `IN_PROGRESS`, `COMPLETED`,
  `CANCELLED`. `COMPLETED` só via `complete()`.
- **Rateio `VALUE` (padrão) e `QUANTITY`** em `imports.allocation_method`.
- **`exchange_rates` e `suppliers`, que existiam sem código**, ganharam
  repository/service/API. Cotação vigente na data é usada automaticamente quando
  não informada; `supplier_id` passa a ser validado por tenant (fecha o vazamento
  cross-FK anotado na 2.4).
- Listagem de despesas/itens com paginação e filtros; `Request::query()`.
- Estado verde: 148 testes / 622 asserções; `composer lint` em 98 arquivos;
  probe MySQL 21/21 (incl. prepares nativos); smoke HTTP da API verde.

---

### Etapa 4 — Catálogo, produtos e preços (EPIC 05)
**Commit:** `42e3db2` (2026-09-28) — *a mensagem diz "etapa 3"; a Etapa 3 já tinha saído em `5e38611`*

- **Categorias e marcas** ganharam repository/service/API. A categoria valida o pai
  e bloqueia ciclo (não pode ser pai de si mesma nem ser movida para baixo de si
  mesma). `status` é validado e gravado em maiúsculo no `create` e no `update`.
- **O preço passou a ser gravado, não só sugerido** — era o buraco real desta etapa.
  `ProductService::withPricedDefaults()` escreve a linha de `product_prices` antes
  de salvar: custo explícito tem precedência, senão entra o custo real do item
  concluído; com `margin` calcula `sale_price`/`minimum_price`; com `sale_price`
  explícito recalcula a margem para o preço salvo (`PricingService::forSalePrice()`).
- **Trocar o item de importação vinculado recalcula o custo** — antes o
  `product_prices` continuava descrevendo o item antigo.
- Cadastro de produto + preço + estoque em **uma transação**.
- `default_import_item_id` passou a ser de fato gravado (antes era aceito e
  ignorado). Exclusão → desativação: 400 quando há estoque, movimentação ou venda;
  sem isso, `INACTIVE` preservando o registro.
- Filtros (categoria, marca, status, busca), paginação, `GET /products/{id}`,
  `GET /products/{id}/price-suggestion` e tela `GET/POST /produtos`.
- Decisão do usuário: **mark-up sobre custo** (`preço = custo × (1 + margem/100)`).
- Estado verde: 197 testes / 799 asserções; `composer lint` em 107 arquivos;
  probe MySQL 27/27; smoke HTTP 22/22.

#### Os três bugs que o MySQL pegou e o SQLite deixou passar

O probe no MySQL real se pagou várias vezes nesta etapa. Vale registrar, porque o
padrão — *teste SQLite verde, produção quebrada* — é o que mais custaria tempo:

1. **`sale_items` não tem `tenant_id`** (o escopo vem de `sales`). A verificação de
   exclusão consultava `si.tenant_id` e lançaria erro em qualquer banco real.
   **O fixture de teste também estava errado**, repetindo o erro — por isso os 49
   testes passavam. Corrigi o repositório e alinhei o schema de teste ao real.
2. **Placeholder repetido** — a busca escrevia
   `(p.name LIKE :search OR p.sku LIKE :search)`. Com
   `ATTR_EMULATE_PREPARES = false` isso é `HY093`; o SQLite reutiliza o marcador e
   não acusa nada. Dois placeholders distintos resolveram.
3. Depois de corrigir 1 e 2 o `HY093` voltou em outro ponto, o que provou que a
   causa era a classe do bug (marcador repetido), não a consulta de vendas.

**Lição registrada:** após o achado, auditei os 14 repositories — 131 queries, nenhum
placeholder repetido. Quando um `HY093` aparece, ele é da família, não da linha.

---

### Etapa 5 — Estoque transacional e rastreabilidade (EPIC 06)
**Commit:** `08b25df` (2026-10-01) — *mensagem "etapa 4 e 5"*

- **A cadeia fecha:** concluir uma importação dá entrada no estoque de cada produto
  vinculado, gravando `IMPORT_ENTRY`. O vínculo é `products.default_import_item_id`,
  escolhido na tela de produto da Etapa 4. Fechamento e entrada de estoque rodam na
  **mesma transação** — `ImportRepository::freeze()` virou `freezeWithin()` para poder
  participar da transação aberta por fora.
- **Idempotência por item:** se já existe `IMPORT_ENTRY` para o `import_item_id`, a
  entrada é pulada. Sem isso, reabrir e concluir de novo somaria a mercadoria duas
  vezes.
- **Regra de saldo negativo: proibido, sem alçada.** O serviço dá a mensagem de erro;
  três `CHECK` no banco garantem mesmo assim (`quantity >= 0`,
  `reserved_quantity >= 0`, `reserved_quantity <= quantity`). A regra é dupla de
  propósito: a constraint cobre o que um INSERT ou script direto faria.
- **Reserva não mexe no saldo físico**, só em `reserved_quantity`; liberação é a
  operação oposta. Consumo debita **saldo e reserva na mesma movimentação** — não dá
  para fazer em dois passos: com saldo 10 e reserva 10, baixar 5 primeiro deixaria
  `reserved(10) > quantity(5)` e a constraint derrubaria a operação no meio.
- Ajuste manual exige `stock.adjust` **e justificativa obrigatória**. Resumo de
  rastreabilidade por `import_item_id` no lugar de lote (ver decisão abaixo).
- Telas `/estoque` (saldo, disponível, reservado, alerta de reposição) e
  `/estoque/movimentacoes` (histórico com filtros). Ajuste, reserva, liberação e
  consumo são pela API, sem tela própria — decisão consciente de escopo.
- Estado verde: 246 testes / 1045 asserções; `composer lint` em 113 arquivos;
  probe MySQL 16/16; smoke HTTP 29/29.

#### Decisão de escopo: rastreio por lote foi adiado

`product_lots` e `stock_movements.lot_id` **não** existem. A rastreabilidade existe por
`import_item_id`: toda entrada sabe de qual compra veio. Lote com custo congelado ficou
como P0 de etapa própria, registrado no `roteiro.md`. Sem essa decisão, o lote puxaria
a Etapa 5 para dentro da de vendas (qual lote o pedido consome).

#### O bug multi-tenant que o smoke HTTP pegou e os testes não

O `ensureRow()` filtrava `stock` por `tenant_id`, mas **não conferia de quem era o
produto**. Um `product_id` de outra empresa não encontrava linha, entrava no `INSERT` e
criava `stock` no tenant de quem perguntou, apontando para o produto alheio — devolvendo
200 com saldo 0 e, pior, fazendo o nome e o SKU do outro aparecerem na lista de quem
perguntou. O filtro por tenant na tabela não segura: a linha criada *é* do tenant que
perguntou.

Corrigido com `assertProductInTenant()`, que devolve **404** (para quem perguntou o
produto não existe; dizer "é de outra empresa" já entregaria informação indevida).

O teste que deveria cobrir isso — `testAdjustingProductOfAnotherTenantFails` — **passava
por motivo errado**: chamava `bootApplication()` sem login, então não havia tenant
ativo e o repositório lançava `MissingTenantException` antes de olhar o produto. Troquei
por login real e acrescentei as duas rotas que faltavam (`balance()` e
`setMinimumQuantity()`, as duas que passavam por `ensureRow()`), verificando também que
nenhuma linha de estoque é criada.

**Lição registrada:** um teste de isolamento que não autentica não testa isolamento —
falha por falta de contexto, não por defeito. Autentique antes de afirmar que o
tenant está errado.

#### Paginação que existia só na meta

`balances()` não tinha `LIMIT`, mas a API e a tela anunciavam `page`/`per_page`. O
`meta` dizia uma coisa e `data` trazia a lista inteira. O `LIMIT` foi para dentro do
repositório, e `countBalances()` passou a usar o mesmo filtro de `balances()` — duplicar
as condições nos dois é o jeito clássico de `total` e `data` começarem a discordar.
Verifiquei que o teste novo falha quando o `LIMIT` é removido, para não ser outro
teste decorativo.

---

### Etapa 6 — Clientes e CRM (EPIC 07)
**Commit:** `7f507ce` (2026-10-01) — *mensagem "etapa nem sei"*
**Estado:** `[~]` implementado e coberto por testes; **falta a validação no MySQL real** (ver "Dívida desta entrada")

- **Nenhuma migration e nenhuma tabela nova.** A etapa só **leu** `sales`, que já
  existia desde a baseline. Nenhuma escrita em venda foi feita aqui — isso é Etapa 7.
- `CustomerRepository` + `CustomerService` + API + telas. Busca por nome, documento ou
  contato; filtros de situação e de "já comprou"; paginação com `total` vindo do mesmo
  filtro da `data` (mesma lição da Etapa 5 sobre `countBalances()`).
- **Documento gravado em dígitos.** "123.456.789-01" e "12345678901" são o mesmo CPF.
  Guardar as duas formas faria o `UNIQUE (tenant_id, document)` **não** pegar a
  duplicata — as strings diferem — enquanto a busca por documento mostraria duas
  linhas para a mesma pessoa. Vazio vira `NULL`, não `""`, porque `NULL` é distinto no
  índice único e duas strings vazias colidiriam. O índice do banco passa a ser a fonte
  da verdade.
- **`DELETE` é desfecho, não erro.** Cliente com venda concluída é **desativado**; sem
  histórico, é **apagado**. A API responde 200 nos dois casos e distingue no corpo
  (`blocked`, `purchases`) em vez de lançar exceção. Um DELETE tem dois resultados
  legítimos e legíveis; lançar obrigaria o controller a adivinhar de onde veio o erro.
  `sales.customer_id` é `ON DELETE SET NULL`, então a remoção física apagaria o vínculo
  em silêncio se a pré-condição falhasse — por isso a garantia é no serviço.
- **Só venda `COMPLETED` conta.** Vale para o resumo e para a decisão de apagar: uma
  venda `CANCELLED` não é histórico, e o cliente volta a ser apagável.
- Resumo comercial: `purchase_count`, `total_spent`, `total_profit`, `average_ticket`,
  `last_purchase_at`. O `AVG` usa `NULLIF(total, 0)` para não inflar nem afundar o
  ticket médio com venda de brinde. A listagem agrega o mesmo resumo por `LEFT JOIN`,
  com o filtro de tenant e de `COMPLETED` **repetido** de propósito — é o que impede a
  lista e o detalhe de contarem coisas diferentes.
- Todas as escritas gravam auditoria (`customer.create`, `.update`, `.block`, `.delete`):
  cadastro de cliente é dado pessoal.
- Telas `/clientes` (lista com filtros + formulário nativo de cadastro) e `/clientes/{id}`
  (ficha, resumo e histórico). Edição e bloqueio só pela API, como em produtos e estoque.
- **Permissões novas:** `customers.edit` e `customers.delete`. `customers.view` e
  `customers.create` já estavam no seed. Total semeado: 24.
- 31 testes em `tests/Integration/CustomerTest.php`, incluindo os três casos que mais
  importam: cliente de outro tenant em 404, viewer que lê mas não escreve, e formulário
  nativo com documento duplicado respondendo erro amigável (não erro do driver).

#### Dívida desta entrada

1. **Sem probe no MySQL real e sem smoke HTTP.** A etapa mexe em SQL (subquery de
   agregação, `LEFT JOIN`, `ON DELETE SET NULL` dependente do schema real) e a regra
   escrita no topo deste diário exige probe quando toca banco. Os testes usam SQLite em
   memória e o fixture é escrito à mão — foi exatamente essa combinação que escondeu o
   `si.tenant_id` na Etapa 4. **Antes de dar a Etapa 6 por validada, rodar o probe e o
   smoke**, e conferir `ApiIntegrationTestCase` contra as migrations.
2. **`docs/openapi-mvp.yaml` continua congelado em 2026-08-01.** São ~30 endpoints já
   implementados sem documentação — todos os de `/stock*` e `/customers*`, além de
   `/users*`, `/roles*`, `/exchange-rates`, `/suppliers`, `/categories`, `/brands`,
   `/imports/{id}/reopen` e `/products/{id}/price-suggestion`. Viola o checklist que o
   próprio roteiro exige para toda rota nova.
3. **Bug de merge no menu:** `resources/views/partials/sidebar.php` renderizava o grupo
   "Operacao" com o link Dashboard **duas vezes** (copy/paste da Etapa 6). Corrigido —
   era o único bloco duplicado.

#### Dívida de ferramenta que vai atrapalhar a próxima etapa

**`composer test` não termina.** A suíte leva ~6min30s e estoura o `process-timeout`
de 300s do Composer, que aborta com `The following exception is caused by a process
timeout` — sem ser falha de teste. Rodar direto:

```powershell
vendor\bin\phpunit --no-coverage
```

Pendência de infra: `composer.json` precisa de `"config": { "process-timeout": 900 }`
(ou `COMPOSER_PROCESS_TIMEOUT=0`). Sem isso, `composer test` é um falso negativo em
qualquer etapa a partir de agora.

---

### Manutenção — 2026-10-02
**SEM COMMIT** (working tree) · sem mudança de código de aplicação

Sessão dedicada a fechar a defasagem entre o código e a documentação, sem avançar etapa.

- **O que era verdade e o código já não era:** o bloco "Onde parou" afirmava que as
  Etapas 4 e 5 estavam sem commit e que a Etapa 6 era a próxima. As três coisas já
  tinham acontecido (`08b25df`, `42e3db2`, `7f507ce`). Corrigido.
- **`SEM COMMIT` removido** dos títulos das Etapas 3, 4 e 5, com o hash e a data reais de
  cada uma. Registrou-se também que a mensagem de `42e3db2` diz "etapa 3" e a de
  `7f507ce` diz "etapa nem sei" — é a padronização de commits que a Etapa 10 tem em
  aberto, mas o mapa etapa↔commit agora está explícito aqui.
- **`roteiro.md`**: os 6 itens `P1` da Etapa 6 marcados como concluídos (o código já os
  tinha), com as duas decisões de escopo da etapa e as permissões novas; cabeçalho
  "Estado atual do código" corrigido; barra da Etapa 6 completada e a convenção da
  barra explicitada (conta `P0` + `P1`, não `P2` — por isso a Etapa 5 segue pela metade).
- **Bug de menu corrigido:** `sidebar.php` renderizava o grupo "Operacao" com o Dashboard
  duplicado, resíduo de copy/paste da Etapa 6. O usuário com permissão `dashboard.view`
  via o mesmo link duas vezes no menu.
- **Dois typos** no diário corrigidos ("importaçãovinculado", "oPedido").
- **Números do estado atual reconferidos no código:** 88 rotas (62 API), 24 permissões,
  71 arquivos em `app/`, 17 de teste, 118 arquivos no lint, 16 migrations.
- **Validação desta sessão:** `composer lint` verde (118 arquivos) e suíte completa
  verde (277 testes / 1247 asserções). Esta é a **primeira** rodada que cobre as edições
  finais em `CustomerRepository.php` — o último `test-results` no repositório era de
  16:39 e o arquivo foi modificado às 16:43, então havia ~4 minutos de código sem
  nenhuma cobertura. **Probe no MySQL e smoke HTTP continuam pendentes para a Etapa 6.**

---

## Estado atual do repositório (2026-10-02)

| Métrica | Valor |
|---|---|
| Suíte de testes | **277 testes / 1247 asserções, verde** (rodada em 2026-10-02) |
| `composer lint` | **118 arquivos**, sem erro de sintaxe |
| Probe MySQL real | 16/16 — **da Etapa 5; a Etapa 6 ainda não foi sondada** |
| Smoke HTTP | 29/29 — **da Etapa 5; a Etapa 6 ainda não foi sondada** |
| Migrations | 16 (`000001` a `000016`) — a Etapa 6 não exigiu migration |
| Tabelas | 30 (nenhuma nova; nem `product_lots` nem tabela de cliente) |
| Rotas | **88** (62 de API) — a Etapa 6 somou 9 (3 web + 6 API) |
| Permissões semeadas | **24** — a Etapa 6 criou `customers.edit` e `customers.delete` |
| Arquivos PHP em `app/` | **71** |
| Arquivos de teste | **17** |
| Último commit | `7f507ce etapa nem sei` (contém a Etapa 6) |
| Banco MySQL local | estado desconhecido desde a Etapa 5 — reconferir antes do probe |
| Endpoints no OpenAPI | 20 de 88 — **`docs/openapi-mvp.yaml` congelado em 2026-08-01** |

Migrations mais recentes já aplicadas no MySQL local: `000013` (unique em
`tenants`), `000014` (`users.auth_version`), `000015`
(`imports.allocation_method` e `completed_at`), `000016` (três `CHECK` de invariante
em `stock` e dois índices de histórico em `stock_movements`).

**Correção em relação ao registro anterior:** a tabela acima dizia "Etapas 4 e 5 ainda
NÃO commitadas" e "246 testes / 113 arquivos / 79 rotas / 22 permissões". Os commits
aconteceram (`08b25df` e `42e3db2`) e a Etapa 6 já está em `7f507ce`. Os números acima
são reconferidos no código em 2026-10-02.

## Onde parou

- **Etapa 6 (Clientes e CRM) implementada e commitada em `7f507ce`.** A Etapa 7 é o
  ponto exato de retomada.
- **O que falta para dar a Etapa 6 por validada:** probe no MySQL real e smoke HTTP.
  A suíte e o lint estão verdes, mas os dois correm em SQLite e não cobrem o schema
  real. Ver "Dívida desta entrada" na Etapa 6.
- `docs/openapi-mvp.yaml` está congelado desde 2026-08-01, com ~30 endpoints
  implementados e não documentados, contra o checklist que o próprio roteiro exige.
- **`composer test` estoura o `process-timeout` do Composer** com a suíte atual
  (~6min30s > 300s) e reporta falso negativo. Rodar `vendor\bin\phpunit --no-coverage`.
- Rastreio por lote (`product_lots`) adiado para etapa própria, por decisão de escopo;
  rastreabilidade atual é por `import_item_id`.
- A reserva de estoque da Etapa 5 **não se prende a venda** — `reserved_quantity` é um
  número no produto. O encaixe é o primeiro item da Etapa 7.
- Próxima etapa do roteiro: **Etapa 7 — Vendas e checkout (EPIC 08)**.

## Decisões técnicas consolidadas

- Tenant ativo vem exclusivamente de `$_SESSION['auth']`; repositories de negócio
  passam por `TenantScopedRepository`.
- MySQL usa `ATTR_EMULATE_PREPARES = true`. Placeholders `:nome` **não** podem se
  repetir na mesma query (prepares nativos falham com `HY093`).
- Formulários nativos só postam em rotas web com token CSRF; a API exige
  `Content-Type: application/json` **mesmo sem corpo** (defesa CSRF).
- `users.email` é único global (decisão de login).
- Fuso do tenant é aplicado só na exibição.
- `imports.status` canônico: `PLANNED`/`IN_PROGRESS`/`COMPLETED`/`CANCELLED`.
- Contrato de validação antes de escrita: services validam tudo antes de tocar o
  banco; operações multi-passo usam transação.
- **Margem = mark-up sobre custo** (decisão do usuário na Etapa 4):
  `preço = custo × (1 + margem/100)` e `margem = (preço − custo)/custo × 100`.
  Os manuais aceitam as duas leituras; esta é a coerente com os exemplos de
  "preço sugerido" e com a coluna `product_prices.margin`.
- **Rastreabilidade de estoque é por `import_item_id`, não por lote** (decisão de
  escopo na Etapa 5): toda entrada em estoque sabe de qual compra veio, sem
  `product_lots`. Lote com custo congelado ficou para etapa própria.
- **Saldo negativo é proibido, sem alçada**: erro no serviço e `CHECK` no banco.
- **Consumo de reserva debita saldo e reserva na mesma movimentação**; fazer em dois
  passos viola `reserved <= quantity` no meio da transação.
- Qualquer caminho que receba `product_id` de fora passa por
  `StockRepository::assertProductInTenant()`, que devolve 404: o filtro por
  `tenant_id` na tabela não protege o produto, só a linha.
- **Tabelas de apoio reusam as permissões do domínio que as alimenta.** Categoria e
  marca usam `products.view/create/edit`: quem não vê produtos não vê o catálogo.
- **`default_import_item_id` é informado, não derivado.** Escolher o item por SKU
  seria ambíguo: itens de importações diferentes podem trazer o mesmo SKU de
  fabricante, e o custo seria semeado errado sem aviso.
- **Identificador de documento é gravado canônico (só dígito) e vazio vira `NULL`.**
  Sem isso o `UNIQUE` do banco não pega a duplicata e a busca mostra duas linhas para a
  mesma pessoa. `NULL` é distinto no índice; string vazia não.
- **`DELETE` de cliente é desfecho, não erro**: com venda concluída, desativa; sem
  histórico, apaga. A API responde 200 nos dois e distingue no corpo. `sales.customer_id`
  é `ON DELETE SET NULL`, então a garantia da pré-condição fica no serviço.
- **Só venda `COMPLETED` conta como histórico comercial** — para o resumo e para
  decidir entre bloquear e apagar.
- **Listagem e detalhe repetem o filtro de tenant e de `COMPLETED` de propósito:** é o
  que impede `total`/`data` e lista/detalhe de contarem coisas diferentes.

## Pendências e limitações conhecidas

- **Etapa 2**
  - `P2` Rate limit progressivo de login e política de senha forte (2FA fora do MVP).
  - `P2` Envio de e-mail de recuperação (`manual/20`) — variáveis já no `.env`.
  - Enumeração de e-mail possível (resposta "e-mail já está em uso").
  - `roles.tenant_id` é nullable no schema, mas o CRUD exige tenant.
  - Telas cobrem listagem/detalhe/bloqueio/permissões; criar/editar/excluir
    usuário e perfil só via API.
  - Cadastro de tenant sem UI (pertence ao painel SaaS, P2).
  - Idioma armazenado mas interface só em pt-BR.
- **Etapa 3**
  - Rateio por **peso** e por **combinação** não implementados: falta campo de peso
    em `import_items` e o manual não define a fórmula.
  - Rateio **manual** adiado (sem via de entrada por item).
  - Reprocessamento com **diff** não existe; há só `reopen` simples e auditado.
  - Checagem de status fora da transação do `freeze()` (sem `SELECT ... FOR UPDATE`).
  - Anexos/comprovantes de despesa (storage) não implementados.
- **Etapa 4**
  - **Sem alerta de preço abaixo do mínimo.** A margem efetiva é calculada e gravada,
    mas nada impede vender abaixo de `minimum_price` — a verificação pertence à Etapa 7
    (vendas/checkout), onde a venda é efetuada.
  - `P2` Importação/exportação de catálogo em CSV/Excel e código de barras com scanner
    não iniciados. O schema já tem `products.barcode`, guardado mas sem uso.
  - `product_prices` é 1:1 com produto: não há histórico de tabela de preços nem
    validade por período.
  - `DELETE` sempre desativa, mesmo sem histórico. Deliberado: exclusão física
    quebraria `sale_items`/`stock_movements`.
  - Checagem de unicidade de SKU e de status fora da transação de gravação — duas
    requisições simultâneas com o mesmo SKU podem passar pela checagem e uma falhar
    no índice único (a mensagem de erro resultante é a do driver, não a de domínio).
- **Etapa 5**
  - **Rastreio por lote não implementado** (`product_lots`, `lot_id`). Adiado por
    decisão de escopo: a rastreabilidade atual é `stock_movements.import_item_id`.
    Lote com custo congelado é P0 de etapa própria.
  - **Ajuste, reserva, liberação e consumo só existem como API.** As telas de estoque
    são de leitura; não há formulário de ajuste nem tela de reserva.
  - **Reserva não se prende a venda.** `reserved_quantity` é um número no produto,
    sem vínculo com pedido; `sale_item_id` existe na movimentação mas o consumo pelo
    checkout (Etapa 7) ainda não foi feito.
  - Inventário periódico e transferência entre unidades (`P2`) não iniciados.
  - Saldo negativo é proibido por serviço e por `CHECK`. Uma futura alçada para
    permitir saldo negativo exigiria remover a constraint — não foi prevista.
  - Checagem de disponibilidade na reserva é leitura sem `SELECT ... FOR UPDATE`: duas
    reservas simultâneas podem passar pela checagem, e a segunda é barrada pela
    constraint do banco (erro do driver, não mensagem de domínio).
- **Etapa 6**
  - **`P2` Fichas, anotações e timeline.** Existe `customers.notes` como texto livre no
    cadastro, sem data/autor e sem registro de interação.
  - **`P2` Segmentação, etiqueta e lista de aniversário/ano-versário** não iniciadas.
  - **Edição e bloqueio de cliente só existem como API.** As telas são de leitura mais o
    formulário nativo de cadastro — mesma decisão de escopo de produtos e estoque.
  - Checagem de documento duplicado é leitura fora da transação de gravação: dois
    cadastros simultâneos com o mesmo documento podem passar pela checagem e um falhar
    no índice único (erro do driver, não mensagem de domínio).
  - O resumo comercial é **derivado das vendas** e considera só `COMPLETED`. Enquanto a
    Etapa 7 não existir, ele é sempre zero em um banco de verdade — os testes semeam
    venda para exercitar o caminho. Não ler isso como cliente sem histórico.
  - Sem validação no MySQL real nem smoke HTTP (ver a entrada da Etapa 6).
- **Transversal**
  - **`docs/openapi-mvp.yaml` congelado em 2026-08-01:** 20 de 88 endpoints
    documentados. Viola o checklist do próprio `roteiro.md`.
  - **`composer test` estoura o `process-timeout` de 300s** e reporta falso negativo.
    Rodar `vendor\bin\phpunit --no-coverage`, ou adicionar
    `"config": { "process-timeout": 900 }` no `composer.json`.
  - **Mensagens de commit não seguem convenção** (`etapa nem sei`, `etapa 3` na Etapa 4).
    A Etapa 10 ainda tem esse item em aberto.
  - PHPStan/PHPCS pendentes de instalação.
  - **Fixture de teste pode divergir do schema real** e esconder bugs. Ao adicionar
    coluna, conferir `ApiIntegrationTestCase` contra a migration. Foi exatamente isso
    que escondeu o erro de `si.tenant_id` na Etapa 4.
  - **Teste de isolamento que não autentica não testa isolamento** — ver Etapa 5.
  - **Bootstrap 5 nunca foi adotado**, apesar de `manual/69` fixá-lo na stack: todas as
    telas usam `<style>` inline. Está na Etapa 9 como P1.

## Bloqueios

- `phpstan/phpstan` e `squizlabs/php_codesniffer` não instalados (Packagist
  estava indisponível na Etapa 1). Comando previsto:
  `composer require --dev phpstan/phpstan squizlabs/php_codesniffer`.
- PHP 8.3 não disponível localmente (só 8.4); alvo do CI não é testado igual.
- GitHub Actions nunca rodou em push real.
- **BLOQUEIO do usuário:** servidor SMTP ainda não configurado (envio de e-mail).

## Próximo passo

1. **Etapa 7 — Vendas e checkout (EPIC 08).** É a próxima da sequência crítica de valor
   (3 → 5 → 7 → 8) e o elo que falta: as 6 tabelas de venda existem e nenhuma rota foi
   implementada. O primeiro item é ligar `reserved_quantity` (Etapa 5) à venda, que hoje
   é um número solto no produto.
2. **Antes de começar a Etapa 7, fechar as pendências da Etapa 6** — probe no MySQL real
   e smoke HTTP. Abrir a próxima etapa com a anterior só validada em SQLite repete
   exatamente o padrão que já custou tempo duas vezes.
3. Dívida que convém atacar cedo, toda listada em **Transversal**: o
   `composer.json` sem `process-timeout`, o `docs/openapi-mvp.yaml` congelado, o rateio
   por peso/combinação (definir o campo de peso), o rate limit de login e a tela de
   ajuste de estoque.

## Como validar

```powershell
composer lint                          # sintaxe (rápido)
vendor\bin\phpunit --no-coverage       # 277 testes / 1247 asserções (~6min30s)
composer migrate                       # aplica migrations pendentes
composer seed                          # idempotente
php -S localhost:8000 -t public public/index.php   # smoke manual
```

- **`composer test` não serve para a suíte atual:** o `process-timeout` de 300s do
  Composer aborta o processo e a falha aparece como timeout, não como teste vermelho.
  Use `vendor\bin\phpunit` direto até o `composer.json` ser ajustado.

- Probe de banco e smoke HTTP foram executados com scripts temporários fora do
  repositório; o banco local é conferido e restaurado ao seed ao final.
- **O smoke HTTP paga mais que os testes aqui.** Na Etapa 5 ele achou um bug
  multi-tenant que 46 testes não pegaram, porque o isolamento estava coberto por um
  teste que falhava por outro motivo.

## Convenções de verificação usadas nesta base

- Toda etapa fecha com: suíte verde + lint + (se toca SQL) probe no MySQL real com
  e sem prepares nativos + smoke HTTP do fluxo principal.
- Dados de teste/probe são sempre removidos; o banco volta ao estado do seed.
- Escrever teste que reproduz o caminho **real** (ex.: corpo urlencoded presente,
  como no SAPI) — vários bugs só apareceram por causa disso.
- **O probe no MySQL real não é redundante com o SQLite.** Ele é o que pega:
  - coluna que o schema real não tem (e que o fixture repetia por engano);
  - placeholder repetido, que o SQLite aceita e o MySQL recusa (`HY093`);
  - diferença de DECIMAL/REAL, `LIKE` com acento e `LIMIT`/`OFFSET`.
- Ao cadastrar coluna nova, conferir o fixture de teste contra a migration — o
  caminho de falha mais silencioso que existe aqui: teste verde, banco quebrado.
- Ao aparecer um `HY093`, tratar como família (marcador repetido em qualquer query),
  não como bug da linha em que ele estourou. Auditar todos os repositories.
