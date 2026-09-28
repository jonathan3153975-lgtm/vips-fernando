# Diário de Bordo — ImportControl

Registro contínuo de evolução do projeto. Complementa o `roteiro.md` (que é o
plano de tarefas) e o `manual/` (que é a especificação). Aqui fica o que
**aconteceu de fato**, na ordem, com as decisões tomadas e o estado real do
código.

- **Última atualização:** 2026-09-27
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
**SEM COMMIT** (working tree, 2026-09-27)

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

## Estado atual do repositório (2026-09-27)

| Métrica | Valor |
|---|---|
| Suíte de testes | 148 testes / 622 asserções, verde |
| `composer lint` | 98 arquivos, sem erro de sintaxe |
| Migrations | 15 (`000001` a `000015`) |
| Tabelas | 30 |
| Rotas | 57 (37 de API) |
| Permissões semeadas | 22 |
| Arquivos PHP em `app/` | 55 |
| Arquivos de teste | 14 |
| Último commit | `e39f066 etapa 2` |
| Banco MySQL local | restaurado ao estado do seed (1 tenant, 5 perfis, 1 usuário, 22 permissões) |

Migrations mais recentes já aplicadas no MySQL local: `000013` (unique em
`tenants`), `000014` (`users.auth_version`), `000015`
(`imports.allocation_method` e `completed_at`).

## Onde parou

- **Etapa 3 implementada e validada, mas ainda NÃO commitada.** O working tree tem
  8 arquivos modificados e 8 novos (`git status`). É o ponto exato de retomada.
- Próxima etapa do roteiro: **Etapa 4 — Catálogo, produtos e preços (EPIC 05)**.
- Antes de seguir, decisão pendente do usuário: commitar a Etapa 3 primeiro.

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
- **Transversal**
  - PHPStan/PHPCS pendentes de instalação.

## Bloqueios

- `phpstan/phpstan` e `squizlabs/php_codesniffer` não instalados (Packagist
  estava indisponível na Etapa 1). Comando previsto:
  `composer require --dev phpstan/phpstan squizlabs/php_codesniffer`.
- PHP 8.3 não disponível localmente (só 8.4); alvo do CI não é testado igual.
- GitHub Actions nunca rodou em push real.
- **BLOQUEIO do usuário:** servidor SMTP ainda não configurado (envio de e-mail).

## Próximo passo

1. Commitar a Etapa 3 (ou revisar antes).
2. Seguir para a **Etapa 4 — Catálogo, produtos e preços (EPIC 05)**.
3. Itens de dívida que convém atacar cedo: rateio por peso/combinação (definir o
   campo de peso) e rate limit de login.

## Como validar

```powershell
composer lint                          # sintaxe
composer test                          # 148 testes / 622 asserções
composer migrate                       # aplica migrations pendentes
composer seed                          # idempotente
php -S localhost:8000 -t public public/index.php   # smoke manual
```

- Probe de banco e smoke HTTP foram executados com scripts temporários fora do
  repositório e removidos ao final; o banco local foi restaurado ao seed.

## Convenções de verificação usadas nesta base

- Toda etapa fecha com: suíte verde + lint + (se toca SQL) probe no MySQL real com
  e sem prepares nativos + smoke HTTP do fluxo principal.
- Dados de teste/probe são sempre removidos; o banco volta ao estado do seed.
- Escrever teste que reproduz o caminho **real** (ex.: corpo urlencoded presente,
  como no SAPI) — vários bugs só apareceram por causa disso.
