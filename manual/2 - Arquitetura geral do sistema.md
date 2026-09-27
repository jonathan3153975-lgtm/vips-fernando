# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 2 — Arquitetura Geral do Sistema

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Arquitetura de Software

---

# 1. Objetivo da Arquitetura

A arquitetura do ImportControl deverá ser construída para atender quatro princípios fundamentais:

- Escalabilidade
- Manutenibilidade
- Baixo Acoplamento
- Alta Coesão

O sistema deverá permitir crescimento constante sem necessidade de grandes refatorações.

Todo o desenvolvimento deverá seguir rigorosamente os princípios SOLID, Clean Code, PSR-12 e MVC.

---

# 2. Filosofia Arquitetural

O projeto utilizará uma adaptação do padrão MVC enriquecido com camadas de serviços e repositórios.

A divisão das responsabilidades será:

```
Controller
        │
        ▼
Validator
        │
        ▼
Service
        │
        ▼
Repository
        │
        ▼
Database
```

Nenhuma regra de negócio poderá existir fora da camada Service.

---

# 3. Fluxo Completo da Requisição

```
Browser

↓

Apache / Nginx

↓

public/index.php

↓

Router

↓

Middleware

↓

Controller

↓

Validator

↓

DTO

↓

Service

↓

Repository

↓

PDO

↓

Database

↓

Repository

↓

Service

↓

Controller

↓

View / JSON

↓

Browser
```

---

# 4. Stack Tecnológica

## Backend

PHP 8.3+

PDO

Composer

PSR-4

PSR-12

PHPUnit

---

## Frontend

HTML5

Bootstrap 5

JavaScript ES6

SweetAlert2

Chart.js

FontAwesome

ApexCharts

DataTables

SortableJS

InputMask

Flatpickr

---

## Banco

MariaDB 11+

Compatível MySQL 8+

Charset:

UTF8MB4

Collation:

utf8mb4_unicode_ci

---

# 5. Estrutura Geral de Pastas

```
/

app/

config/

database/

public/

resources/

routes/

storage/

vendor/

tests/

docs/

```

---

# 6. Estrutura da pasta app

```
app/

Controllers/

Models/

Repositories/

Services/

DTO/

Policies/

Validators/

Requests/

Responses/

Middlewares/

Providers/

Events/

Listeners/

Traits/

Enums/

Interfaces/

Helpers/

Exceptions/

Jobs/

Console/

ViewModels/

Resources/

Mail/

Notifications/

Commands/

```

Cada pasta possui uma responsabilidade única.

---

# 7. Controllers

Responsabilidade única:

Receber a requisição.

Jamais realizar:

SQL

Validação

Regras de negócio

Cálculos

Conversões

Exemplo:

```
TripController

index()

create()

store()

edit()

update()

destroy()

show()
```

O Controller apenas chama Services.

---

# 8. Services

Toda regra do sistema ficará aqui.

Exemplos:

TripService

ProductService

ExpenseService

SaleService

DashboardService

FinanceService

ExchangeRateService

StockService

CustomerService

SupplierService

UserService

TenantService

ReportService

---

Exemplo

```
TripService

createTrip()

closeTrip()

reopenTrip()

calculateTripProfit()

calculateTripCost()

generateTripSummary()

generateDRE()
```

---

# 9. Repository Pattern

Apenas acesso ao banco.

Nenhuma regra.

Exemplo

```
ProductRepository

find()

findById()

findBySku()

save()

update()

delete()

findByTrip()

findAvailable()

```

---

# 10. Models

Representam entidades.

Não devem possuir lógica complexa.

Exemplo

```
Trip

Product

Sale

Expense

Customer

Supplier

```

---

# 11. DTO

DTO será obrigatório.

Jamais enviar Request diretamente para Services.

Exemplo

```
CreateTripDTO

UpdateTripDTO

CreateSaleDTO

CreateExpenseDTO

```

---

# 12. Validators

Toda validação ficará centralizada.

Jamais validar dentro do Controller.

Exemplo

```
TripValidator

SaleValidator

ExpenseValidator

```

---

# 13. Requests

Responsáveis por capturar dados HTTP.

Transformam Request em DTO.

---

# 14. Responses

Padronizam respostas.

Exemplo

```
SuccessResponse

ErrorResponse

ValidationResponse

```

---

# 15. Policies

Controlam permissões.

Exemplo

```
TripPolicy

SalePolicy

UserPolicy

DashboardPolicy

```

---

# 16. Middlewares

Serão utilizados para:

Autenticação

Tenant

Permissões

CSRF

Rate Limit

Sessão

Idioma

Log

---

# 17. Providers

Inicializam componentes.

Exemplo

```
DatabaseProvider

AuthProvider

ViewProvider

RouteProvider

```

---

# 18. Helpers

Funções reutilizáveis.

Exemplo

MoneyHelper

CurrencyHelper

DateHelper

UploadHelper

ImageHelper

SecurityHelper

SlugHelper

CPFHelper

---

# 19. Exceptions

Todas personalizadas.

Jamais utilizar Exception genérica.

Exemplo

```
AuthenticationException

ValidationException

PermissionException

BusinessRuleException

```

---

# 20. Events

Preparação para crescimento.

Exemplo

```
SaleCreated

TripClosed

StockUpdated

CustomerCreated

```

---

# 21. Listeners

Executam tarefas automáticas.

Exemplo

```
UpdateDashboardListener

SendNotificationListener

GenerateFinancialEntryListener

```

---

# 22. Jobs

Preparação para filas.

Exemplo

```
GeneratePDFJob

ExportExcelJob

ImportSpreadsheetJob

```

---

# 23. ViewModels

Preparar dados para View.

Evita lógica HTML.

---

# 24. Organização das Views

```
resources/

views/

layouts/

components/

partials/

pages/

errors/

emails/

```

---

# 25. Layout Principal

```
Header

↓

Sidebar

↓

Topbar

↓

Breadcrumb

↓

Content

↓

Footer

↓

Javascript

```

---

# 26. Componentes Reutilizáveis

Todos deverão ser componentes.

Exemplo

Card

Modal

Button

Badge

Table

Pagination

Search

Breadcrumb

Toast

Dropdown

Alert

Statistic Card

Chart Card

Timeline

Kanban Card

Upload Component

Avatar

---

# 27. Assets

```
public/

css/

scss/

js/

images/

icons/

fonts/

uploads/

```

---

# 28. Organização Javascript

```
js/

core/

modules/

helpers/

services/

components/

pages/

```

---

# 29. Organização CSS

Utilizar SCSS.

Estrutura:

```
scss/

base/

layout/

components/

utilities/

pages/

themes/

```

---

# 30. Padrão de Nomenclatura

Classes

PascalCase

```
TripService
```

Métodos

camelCase

```
calculateProfit()
```

Variáveis

camelCase

```
tripCost
```

Banco

snake_case

```
trip_expenses
```

Colunas

snake_case

```
purchase_price
```

Constantes

UPPER_CASE

```
DEFAULT_CURRENCY
```

---

# 31. Convenções Gerais

Nunca utilizar:

```
$obj

$x

$temp

```

Sempre utilizar nomes descritivos.

Exemplo

```
tripRepository

tripExpense

exchangeRate

```

---

# 32. Injeção de Dependências

Toda dependência será injetada.

Jamais instanciar Services manualmente.

Correto

```
public function __construct(
    private TripService $tripService
)
```

---

# 33. Estrutura das Rotas

```
/

dashboard

/login

/logout

/trips

/products

/customers

/sales

/expenses

/finance

/reports

/settings

/users

```

API

```
/api/v1/

```

---

# 34. Versionamento da API

Desde o início:

```
/api/v1/

/api/v2/
```

---

# 35. Arquitetura de Banco

Utilizar:

Migrations

Seeders

Factories

Nunca criar tabelas manualmente em produção.

---

# 36. Configurações

Todo parâmetro deverá estar em:

```
config/

app.php

database.php

auth.php

mail.php

storage.php

system.php

```

Nunca utilizar valores fixos no código.

---

# 37. Sistema de Logs

Logs separados.

```
storage/logs/

application.log

security.log

finance.log

error.log

```

---

# 38. Estrutura de Uploads

```
uploads/

products/

suppliers/

customers/

expenses/

documents/

company/

```

Jamais armazenar uploads na raiz.

---

# 39. Internacionalização

Preparado para múltiplos idiomas.

```
resources/lang/

pt_BR

en_US

es_ES

```

---

# 40. Objetivos Arquiteturais

Ao término da implementação, a arquitetura deverá permitir:

- adicionar novos módulos sem alterar módulos existentes;
- criar aplicativo mobile utilizando a mesma API;
- integrar marketplaces;
- integrar gateways de pagamento;
- integrar sistemas fiscais;
- suportar milhares de empresas (multi-tenant);
- suportar milhões de registros com boa performance;
- facilitar testes automatizados;
- facilitar manutenção por diferentes desenvolvedores.

---

# Encerramento da Parte 2

Esta arquitetura define a organização técnica do projeto. Nas próximas etapas, todas as regras de negócio, banco de dados e módulos serão desenvolvidos respeitando rigorosamente esta estrutura.

## Próxima Parte (Parte 3)

A Parte 3 abordará exclusivamente a **Arquitetura SaaS Multi-Tenant**, incluindo:

- isolamento de empresas (`tenant_id`);
- autenticação e contexto do tenant;
- gerenciamento de assinaturas e planos;
- estrutura para futuras cobranças;
- onboarding de novos clientes;
- personalização por empresa;
- estratégias de escalabilidade e segurança para um ambiente multiempresa.