# ImportControl

Sistema SaaS multi-tenant para gestão de importações internacionais, estoque, vendas e financeiro.

O eixo do domínio é a **viagem/importação**: cada compra internacional gera custos em moeda estrangeira que precisam ser convertidos, rateados entre os itens adquiridos e incorporados ao custo real do produto — é esse cálculo que determina o lucro.

---

## Stack

| Item | Versão |
|---|---|
| PHP | 8.3+ (testado em 8.3 e 8.4) |
| Banco | MariaDB 11+ / MySQL 8+ (SQLite usado nos testes) |
| Acesso a dados | PDO |
| Arquitetura | MVC + Service Layer + Repository Pattern |
| Frontend | Views server-rendered + Bootstrap 5 (em migração) |
| Autenticação | Sessão web segura com RBAC por tenant |
| API interna | REST versionada em `/api/v1` |
| Testes | PHPUnit 11 |
| Dependências | vlucas/phpdotenv |

---

## Pré-requisitos

- PHP 8.3 ou superior
- Extensões: `pdo`, `pdo_mysql`, `mbstring`
- Composer 2
- MariaDB 11+ ou MySQL 8+ (opcional para rodar apenas os testes)

---

## Instalação

```bash
git clone <repositorio>
cd vips-fernando
composer install
```

Configure o ambiente:

```bash
cp .env.example .env
```

### 1. Criar o banco

O repositório traz um script local, **não versionado**, que cria o banco e um usuário dedicado:

```bash
# abra o arquivo e rode-o no phpMyAdmin ou no terminal do MariaDB
database/create-database.sql
```

Ele executa:

1. `CREATE DATABASE importcontrol` com `utf8mb4` / `utf8mb4_unicode_ci`
2. `CREATE USER 'importcontrol'@'localhost'` e `'importcontrol'@'127.0.0.1'`
3. `GRANT ALL PRIVILEGES` restrito ao banco `importcontrol`
4. Uma consulta de verificação

> **Por que um usuário dedicado e não o `root`?** Menor privilégio: o usuário do projeto só acessa o banco `importcontrol`. Também evita a mensagem confusa `SQLSTATE[HY000] [2054] auth_gssapi_client`, que o MariaDB do Windows devolve quando a conexão é tentada sem senha — ela sugere um problema de driver, mas na prática é autenticação recusada. Com a senha correta o `root` funciona normalmente.
>
> Troque a senha do script antes de usar em qualquer ambiente compartilhado. O arquivo está no `.gitignore` (`/database/*.sql`) justamente por conter credencial.

### 2. Apontar o `.env` para o usuário criado

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=importcontrol
DB_USERNAME=importcontrol
DB_PASSWORD=importcontrol
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

### 3. Criar as tabelas e os dados

```bash
composer migrate
composer seed
```

`composer migrate` cria 30 tabelas em 12 migrations (mais a tabela de controle `migrations`). `composer seed` é idempotente e cria o tenant de demonstração, os 5 perfis, as 21 permissões e o usuário administrador.

### Comandos de migração

| Comando | O que faz |
|---|---|
| `composer migrate` | Aplica as migrations pendentes. Idempotente. |
| `composer migrate:rollback` | Reverte o **último batch** de migrations, na ordem inversa. |
| `composer migrate:rollback -- 2` | Reverte um batch específico. |
| `composer migrate:fresh` | Reverte **todas** e reaplica do zero. **Destrói todos os dados** — rode `composer seed` depois. |
| `composer seed` | Repovoa os dados básicos. |

Cada execução de `migrate` cria um *batch* (um número de grupo). O `rollback` desfaz um batch inteiro, então o caminho seguro para desfazer uma migration é revertê-la e corrigir o arquivo antes de reaplicar.

`migrate:fresh` exige `--force` quando `APP_ENV=production`:

```bash
composer migrate:fresh -- --force
```

Sobre resiliência: as migrations usam `CREATE TABLE IF NOT EXISTS`, então uma migration que falhou no meio pode ser reexecutada — as tabelas já criadas são preservadas e as pendentes são concluídas. O que **não** acontece é detecção de divergência: se você editar uma migration que já foi aplicada, a tabela existente não é alterada. Depois que houver migration em produção, o fluxo passa a ser sempre adicionar uma migration nova, nunca editar as antigas.

### 4. Subir o servidor

```bash
composer serve
```

Acesse `http://localhost:8000`.

---

## Credenciais do seed

| Campo | Valor |
|---|---|
| E-mail | `admin@demo.local` |
| Senha | `Admin@123` |
| Tenant | Tenant Demo |

---

## Comandos disponíveis

| Comando | Descrição |
|---|---|
| `composer serve` | Servidor de desenvolvimento em `localhost:8000` |
| `composer test` | Executa a suíte de testes |
| `composer lint` | Valida a sintaxe de todos os arquivos PHP |
| `composer migrate` | Aplica as migrations pendentes |
| `composer seed` | Aplica o seed base (idempotente) |

Análise estática (requer as dependências opcionais instaladas):

```bash
composer require --dev phpstan/phpstan squizlabs/php_codesniffer
vendor/bin/phpstan analyse
vendor/bin/phpcs --standard=PSR12 app config database routes tests
```

---

## Estrutura do projeto

```
app/
  Controllers/     Controllers web e de API
  Core/            Application, Router, Database, Session, Csrf, Logger, Request
  Middlewares/     Auth, Guest, Permission e Csrf
  Repositories/    Acesso a dados, sempre filtrado por tenant_id
  Services/        Regras de negócio e orquestração
bootstrap/app.php  Bootstrap da aplicação
config/            Configuração por ambiente
database/          Migrations, seed e o script local de criação do banco
docs/              Contrato OpenAPI
resources/views/   Views server-rendered
routes/web.php     Rotas web e API
storage/logs/      Logs da aplicação
tests/             Testes unitários e de integração
tools/lint.php     Verificação de sintaxe sem dependências
```

---

## Segurança

- **Isolamento multi-tenant:** toda consulta de negócio filtra por `tenant_id`.
- **Sessão:** cookie `httponly` + `SameSite=Lax`; `secure` é obrigatório quando `APP_ENV=production`.
- **Inatividade:** sessões são encerradas após `SESSION_IDLE_TIMEOUT` minutos.
- **CSRF:** todo POST/PUT/PATCH/DELETE web exige o token em `_token` ou no header `X-CSRF-TOKEN`. A API aceita apenas `application/json`, o que impede requisições cross-origin sem preflight.
- **`APP_DEBUG` é ignorado em produção** — a configuração força `debug = false` quando `APP_ENV=production`.
- **Auditoria:** ações sensíveis gravam em `audit_logs`.
- **Logs:** segredos (`password`, `token`, `secret`, `authorization`) são mascarados antes de gravar.

---

## API

O contrato vive em [`docs/openapi-mvp.yaml`](docs/openapi-mvp.yaml).

Endpoints implementados:

| Método | Rota | Permissão |
|---|---|---|
| `GET` | `/health` | — |
| `GET`/`POST` | `/login` | — |
| `POST` | `/logout` | autenticado |
| `GET` | `/dashboard` | `dashboard.view` |
| `POST` | `/api/v1/auth/login` | — |
| `POST` | `/api/v1/auth/logout` | autenticado |
| `POST` | `/api/v1/auth/password-reset` | — |
| `GET`/`POST` | `/api/v1/imports` | `imports.view` / `imports.create` |
| `PUT` | `/api/v1/imports/{id}` | `imports.create` |
| `POST` | `/api/v1/imports/{id}/expenses` | `imports.create` |
| `POST` | `/api/v1/imports/{id}/items` | `imports.create` |
| `POST` | `/api/v1/imports/{id}/complete` | `imports.complete` |
| `GET`/`POST` | `/api/v1/products` | `products.view` / `products.create` |
| `PUT` | `/api/v1/products/{id}` | `products.edit` |
| `GET` | `/api/v1/products/{id}/stock` | `stock.view` |

Erros seguem o padrão `{"message": "..."}`.

---

## Testes

```bash
composer test
```

A suíte sobe um banco SQLite em memória temporária por teste e cobre:

- autenticação e armazenamento de sessão
- bloqueio por permissão (403)
- isolamento entre tenants
- fluxo completo de importação com rateio de despesas
- trilha de auditoria
- proteção CSRF em formulários web
- rejeição de payload não-JSON na API
- health check com banco acessível e inacessível

---

## Deploy

O workflow `.github/workflows/deploy.yml` roda em toda alteração na `main`:

1. Job **Qualidade** — valida sintaxe (`composer lint`) e executa os testes (`composer test`).
2. Job **Deploy** — só roda se a qualidade passou. Instala as dependências com `--no-dev` e envia os arquivos via `rsync` por SSH.

Variáveis de secret necessárias: `SSH_PRIVATE_KEY`, `SSH_HOST`, `SSH_PORT`, `SSH_USER`, `REMOTE_PATH`.

Arquivos excluídos do envio: `.git/`, `.github/`, `.env`, `storage/logs/*`.

### Checklist de release

- [ ] `.env` de produção com `APP_ENV=production` e `APP_DEBUG=false`
- [ ] `composer migrate` executado no servidor
- [ ] `GET /health` retornando `200` com `checks.database.ok = true`
- [ ] Backup do banco configurado e uma restauração testada
- [ ] HTTPS ativo e `SESSION_SECURE_COOKIE=true`

---

## Documentação

A pasta [`manual/`](manual) reúne 70 documentos de produto e arquitetura. Os documentos canônicos do MVP são:

- [`manual/69 — Baseline única do MVP.md`](manual) — stack, escopo fechado e regras definitivas
- [`manual/70 — Backlog técnico do MVP.md`](manual) — épicos e ordem de implementação
- [`roteiro.md`](roteiro.md) — tarefas pendentes por etapa

Documentos anteriores a 68 descrevem versões sobrepostas do mesmo assunto e servem como histórico.
