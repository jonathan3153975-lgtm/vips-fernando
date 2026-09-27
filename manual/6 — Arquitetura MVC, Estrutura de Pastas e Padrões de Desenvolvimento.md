# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 6 — Arquitetura MVC, Estrutura de Pastas e Padrões de Desenvolvimento

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Arquitetura de código, organização e padrões técnicos

---

# 1. Objetivo

Esta seção define como o código-fonte do ImportControl deverá ser organizado.

O objetivo é garantir:

- facilidade de manutenção;
- separação clara de responsabilidades;
- baixo acoplamento;
- reutilização de componentes;
- facilidade de testes;
- facilidade de evolução por múltiplos desenvolvedores.

O sistema deverá seguir uma arquitetura MVC aprimorada com camadas adicionais.

A arquitetura será composta por:
MVC

Service Layer

Repository Pattern

DTO

Dependency Injection

Middleware

Policies

Validators

Events

Jobs


---

# 2. Princípio Fundamental

Cada camada possui uma responsabilidade única.

Nenhuma camada deverá executar responsabilidades de outra.

Exemplo:

## Controller

Recebe requisição.

Não calcula lucro.

---

## Service

Executa regra de negócio.

Não conhece HTML.

---

## Repository

Executa consultas ao banco.

Não decide regras.

---

## View

Exibe informações.

Não executa cálculos.

---

# 3. Fluxo Interno da Aplicação

Fluxo padrão:


Usuário

↓

Browser

↓

Route

↓

Middleware

↓

Controller

↓

Request

↓

Validator

↓

DTO

↓

Service

↓

Repository

↓

Database

↓

Entity/Model

↓

Service

↓

Response

↓

View/API


---

# 4. Estrutura Principal do Projeto

Estrutura inicial:


ImportControl/

│
├── app/
│
├── bootstrap/
│
├── config/
│
├── database/
│
├── public/
│
├── resources/
│
├── routes/
│
├── storage/
│
├── tests/
│
├── vendor/
│
├── composer.json
│
├── .env
│
├── .env.example
│
└── README.md


---

# 5. Diretório app

Responsável pelo núcleo da aplicação.


app/

├── Core/

├── Controllers/

├── Models/

├── Repositories/

├── Services/

├── DTO/

├── Validators/

├── Requests/

├── Responses/

├── Middleware/

├── Policies/

├── Exceptions/

├── Helpers/

├── Interfaces/

├── Traits/

├── Enums/

├── Events/

├── Listeners/

├── Jobs/

└── Providers/


---

# 6. Core

Contém componentes fundamentais.

Estrutura:


Core/

├── Application.php

├── Router.php

├── Request.php

├── Response.php

├── Database.php

├── Session.php

├── Auth.php

├── Container.php

└── View.php


---

# 7. Controllers

Responsáveis pela comunicação HTTP.

Estrutura:


Controllers/

├── Auth/

│ └── LoginController.php

│

├── DashboardController.php

├── TripController.php

├── ProductController.php

├── SaleController.php

├── CustomerController.php

├── FinanceController.php

└── ReportController.php


---

# 8. Regra dos Controllers

Controllers devem ser pequenos.

Exemplo:

Correto:

```php
public function store(Request $request)
{
    $dto = CreateTripDTO::fromRequest($request);

    $trip = $this->tripService->create($dto);

    return redirect('/trips');
}

Errado:

public function store()
{
    INSERT INTO trips...

    calcular lucro...

    enviar email...

}
9. Models

Representam entidades.

Exemplo:

Models/

├── User.php

├── Tenant.php

├── Trip.php

├── Product.php

├── Sale.php

├── Expense.php

└── Customer.php

10. Responsabilidade dos Models

Permitido:

representar dados;
relacionamentos;
conversões simples.

Não permitido:

regras financeiras;
envio de email;
cálculos complexos.
11. Repositories

Camada de persistência.

Estrutura:

Repositories/

├── Interfaces/

│
├── UserRepository.php

├── TripRepository.php

├── ProductRepository.php

├── SaleRepository.php

└── FinanceRepository.php

12. Interfaces de Repository

Todos os repositories deverão possuir contrato.

Exemplo:

interface ProductRepositoryInterface
{

public function find(int $id);

public function save(Product $product);

public function update(Product $product);

}
13. Implementação Repository

Exemplo:

class ProductRepository implements ProductRepositoryInterface
{

public function find(int $id)
{

}

}
14. Services

Camada mais importante da aplicação.

Estrutura:

Services/

├── AuthService.php

├── TenantService.php

├── TripService.php

├── ProductService.php

├── StockService.php

├── SaleService.php

├── FinanceService.php

├── ReportService.php

└── DashboardService.php

15. Responsabilidade dos Services

Exemplos:

TripService
criar viagem;
fechar viagem;
calcular custos;
gerar resumo.
StockService
entrada;
saída;
ajustes;
inventário.
SaleService
criar venda;
validar estoque;
baixar produtos;
calcular lucro.
16. DTO (Data Transfer Object)

DTOs representam dados que trafegam entre camadas.

Estrutura:

DTO/

├── Auth/

├── Trip/

├── Product/

├── Sale/

└── Finance/


Exemplo:

class CreateProductDTO
{

public string $name;

public float $price;

public int $tripId;

}
17. Requests

Responsáveis pela entrada HTTP.

Estrutura:

Requests/

├── TripRequest.php

├── ProductRequest.php

├── SaleRequest.php


Responsabilidades:

capturar dados;
converter tipos;
encaminhar validação.
18. Validators

Todas as validações ficam centralizadas.

Estrutura:

Validators/

├── UserValidator.php

├── ProductValidator.php

├── SaleValidator.php


Exemplo:

Produto:

Obrigatório:

nome;
preço;
quantidade.
19. Middleware

Responsável por interceptar requisições.

Estrutura:

Middleware/

├── AuthMiddleware.php

├── TenantMiddleware.php

├── PermissionMiddleware.php

├── CsrfMiddleware.php

└── LogMiddleware.php

20. Tenant Middleware

Obrigatório em todas as áreas privadas.

Responsável por:

identificar empresa;
carregar contexto;
impedir acesso cruzado.
21. Policies

Controlam autorização.

Estrutura:

Policies/

├── ProductPolicy.php

├── SalePolicy.php

├── UserPolicy.php


Exemplo:

Usuário pode:

editar produto

Mas não:

excluir produto vendido
22. Exceptions

Erros personalizados.

Estrutura:

Exceptions/

├── AuthenticationException.php

├── PermissionException.php

├── BusinessException.php

└── ValidationException.php

23. Helpers

Funções auxiliares.

Estrutura:

Helpers/

├── CurrencyHelper.php

├── MoneyHelper.php

├── DateHelper.php

├── UploadHelper.php

24. Enums

Valores fixos.

Estrutura:

Enums/

├── SaleStatus.php

├── TripStatus.php

├── PaymentMethod.php

└── Currency.php


Exemplo:

enum TripStatus:string
{

case OPEN='OPEN';

case CLOSED='CLOSED';

}
25. Events

Eventos do sistema.

Estrutura:

Events/

├── SaleCreated.php

├── TripClosed.php

└── StockUpdated.php

26. Listeners

Executam ações após eventos.

Exemplo:

Venda criada:

Evento:

SaleCreated

Listeners:

GenerateFinancialEntry

UpdateDashboard

SendNotification
27. Jobs

Processamentos pesados.

Exemplo:

Jobs/

├── GenerateReportJob.php

├── ExportExcelJob.php

└── SendEmailJob.php

28. Providers

Inicialização.

Estrutura:

Providers/

├── AppProvider.php

├── DatabaseProvider.php

├── AuthProvider.php

29. Container de Dependências

Toda dependência deverá ser registrada.

Exemplo:

Container::bind(
ProductRepositoryInterface::class,
ProductRepository::class
);
30. Composer

Autoload PSR-4.

composer.json:

{
 "autoload":{
   "psr-4":{
      "App\\":"app/"
   }
 }
}
31. Configuração por Ambiente

Nunca deixar dados sensíveis no código.

Utilizar:

.env

Exemplo:

DB_HOST=

DB_DATABASE=

DB_USERNAME=

DB_PASSWORD=

APP_KEY=

32. Banco de Dados

Estrutura:

database/

├── migrations/

├── seeders/

└── factories/

33. Migrations

Toda alteração estrutural deverá gerar migration.

Nunca alterar banco manualmente.

34. Seeders

Dados iniciais:

Exemplo:

permissões;
planos;
moedas;
configurações padrão.
35. Testes

Estrutura:

tests/

├── Unit/

├── Feature/

└── Integration/

36. Testes Obrigatórios

Testar:

autenticação;
permissões;
cálculo de custo;
rateio;
vendas;
estoque;
financeiro.
37. Padrão de Código

Obrigatório:

PSR-12

Tipagem:

Sempre utilizar:

declare(strict_types=1);
38. Comentários

Não comentar código óbvio.

Explicar apenas:

decisões complexas;
regras de negócio;
cálculos financeiros.
39. Versionamento

Git obrigatório.

Branches:

main

develop

feature/nome

bugfix/nome

hotfix/nome
40. Critério de Aceitação

Uma funcionalidade somente será considerada pronta quando possuir:

código;
validação;
tratamento de erro;
logs;
testes;
documentação.
Encerramento da Parte 6

Esta arquitetura define como todo o código do ImportControl deverá ser desenvolvido.

Ela garante:

organização;
escalabilidade;
segurança;
facilidade de manutenção;
possibilidade de crescimento como SaaS.
