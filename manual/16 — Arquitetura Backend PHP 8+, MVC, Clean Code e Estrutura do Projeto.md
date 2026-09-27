# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 16 — Arquitetura Backend PHP 8+, MVC, Clean Code e Estrutura do Projeto

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Arquitetura backend, padrões de desenvolvimento e organização do código

---

# 1. Objetivo

Esta seção define a arquitetura técnica do backend do ImportControl.

O objetivo é construir um sistema:

- organizado;
- escalável;
- seguro;
- fácil de manter;
- preparado para crescimento;
- seguindo princípios de Clean Code;
- adequado para uma aplicação SaaS.

---

# 2. Tecnologias Backend

## Linguagem


PHP 8.2+


---

## Banco


MariaDB 10+

ou

MySQL 8+


---

## Dependências

Gerenciador:


Composer


---

## Comunicação Banco

Utilizar:


PDO


com:

- prepared statements;
- transactions;
- tratamento de exceções.

---

# 3. Arquitetura Geral

O sistema utilizará arquitetura:


MVC + Service Layer + Repository Pattern


---

Fluxo:


Usuário

↓

Router

↓

Controller

↓

Service

↓

Repository

↓

Database


---

# 4. Responsabilidade de Cada Camada

---

# View

Responsável pela apresentação.

Exemplos:

- HTML;
- Bootstrap;
- JavaScript.

Não deve conter:

- regras de negócio;
- SQL;
- validações complexas.

---

# Controller

Responsável por:

- receber requisição;
- validar entrada;
- chamar serviços;
- retornar resposta.

Não deve:

- possuir SQL;
- calcular regras complexas.

---

# Service

Camada principal de negócio.

Responsável por:

- cálculos;
- validações;
- regras comerciais;
- integrações.

Exemplo:


VendaService

Calcula lucro

Baixa estoque

Cria financeiro


---

# Repository

Responsável pelo acesso aos dados.

Exemplo:


ProductRepository

Buscar produto

Salvar produto

Atualizar produto


---

# Model

Representação dos dados.

Exemplo:


Product

Customer

Sale


---

# 5. Estrutura de Pastas

Estrutura recomendada:


importcontrol/

├── app/

│

├── Controllers/

│

├── Services/

│

├── Repositories/

│

├── Models/

│

├── Middleware/

│

├── Validators/

│

├── Exceptions/

│

├── Helpers/

│

├── Database/

│

└── Core/

├── config/

├── database/

│

├── migrations/

│

└── seeds/

├── public/

│

├── index.php

│

├── assets/

├── storage/

│

├── uploads/

│

├── logs/

├── routes/

│

├── web.php

│

└── api.php

├── vendor/

├── composer.json

└── .env


---

# 6. Entrada da Aplicação

Arquivo:


public/index.php


Responsável por:

- inicializar aplicação;
- carregar Composer;
- iniciar sessão;
- carregar configurações;
- executar Router.

---

Exemplo:

```php
require '../vendor/autoload.php';

$app = new Application();

$app->run();
7. Composer

Arquivo:

composer.json

Responsável por:

autoload;
dependências;
scripts.

Configuração:

{
 "autoload": {
   "psr-4": {
      "App\\": "app/"
   }
 }
}

Após alteração:

composer dump-autoload
8. Configuração por Ambiente

Utilizar:

.env

Exemplo:

APP_ENV=production

APP_DEBUG=false


DB_HOST=localhost

DB_DATABASE=importcontrol

DB_USERNAME=root

DB_PASSWORD=

Nunca armazenar:

senha;
token;
chave API

no código.

9. Classe Database

Criar:

app/Core/Database.php

Responsável por:

conexão;
transactions;
PDO.

Exemplo:

class Database
{

private PDO $connection;


public function connect()
{

return new PDO(
$this->dsn,
$this->user,
$this->password
);

}

}
10. Router

Criar:

app/Core/Router.php

Responsável por:

mapear URLs;
chamar controllers.

Exemplo:

GET /products

↓

ProductController@index
11. Controllers

Padrão:

NomeController.php

Exemplo:

ProductController

Métodos:

index()

create()

store()

edit()

update()

destroy()

Exemplo:

public function store()
{

$data = request();

$this->service->create($data);

}
12. Services

Toda regra importante deverá ficar aqui.

Exemplo:

SaleService

Responsabilidades:

validar venda;
calcular desconto;
calcular lucro;
atualizar estoque;
gerar financeiro.

Nunca colocar:

UPDATE estoque

diretamente no controller.

13. Repositories

Padrão:

NomeRepository.php

Exemplo:

ProductRepository

Métodos:

find()

findById()

create()

update()

delete()

Exemplo:

$product =
$productRepository
->findById($id);
14. Models

Representam entidades.

Exemplo:

Product.php

Possível estrutura:

class Product
{

public int $id;

public string $name;

public float $cost;

}
15. Dependency Injection

Não criar objetos dentro das classes.

Evitar:

$product =
new ProductRepository();

Utilizar:

public function __construct(
ProductRepository $repository
)
{
$this->repository=$repository;
}

Benefícios:

testes;
manutenção;
baixo acoplamento.
16. Middleware

Criar camada intermediária.

Exemplos:

AuthMiddleware

TenantMiddleware

PermissionMiddleware

CsrfMiddleware

Fluxo:

Request

↓

Middleware

↓

Controller
17. Autenticação

Sistema baseado em:

Session Authentication

Fluxo:

Login

↓

Validar senha

↓

Criar sessão

↓

Carregar usuário

↓

Carregar permissões
18. Controle SaaS Tenant

Toda requisição deverá identificar:

tenant_id

Exemplo:

Usuário:

João

Empresa:

Importadora X

Consulta:

SELECT *

FROM products

WHERE tenant_id = ?
19. Controle de Permissões

Criar:

PermissionMiddleware

Exemplo:

Rota:

/products/delete

Permissão:

products.delete
20. Validação de Dados

Criar:

Validators

Exemplo:

ProductValidator

Validar:

campos obrigatórios;
tamanho;
formato;
regras.
21. Tratamento de Exceções

Criar:

app/Exceptions

Tipos:

DatabaseException

ValidationException

PermissionException

BusinessException
22. Logs

Utilizar:

storage/logs

Registrar:

erros;
exceções;
ações críticas.

Exemplo:

2026-08-01

Venda cancelada

Usuário 10
23. Segurança Backend

Implementar:

SQL Injection

Usar:

PDO Prepared Statements
XSS

Utilizar:

htmlspecialchars()
CSRF

Tokens em formulários.

Password

Utilizar:

password_hash()
Upload

Validar:

extensão;
tamanho;
MIME type.
24. API REST

Preparar estrutura:

/api/v1/

Exemplo:

GET

/api/v1/products

Resposta:

JSON:

{
"id":1,
"name":"Notebook"
}
25. Respostas Padronizadas

Criar:

ResponseHelper

Sucesso:

{
"success":true,
"data":[]
}

Erro:

{
"success":false,
"message":"Erro"
}
26. Jobs Assíncronos Futuros

Preparar:

app/Jobs

Exemplos:

envio de email;
relatórios;
backups;
notificações.
27. Testes

Estrutura:

tests/

Unit/

Feature/

Testar:

Services;
regras financeiras;
permissões.
28. Padrão de Código

Seguir:

PSR-12
Nomes claros

Evitar:

$x;

Preferir:

$totalSale;
Funções pequenas

Evitar:

processEverything()

Preferir:

calculateProfit()

updateStock()

createPayment()
29. Controle de Versão

Utilizar:

Git

Branches:

main

develop

feature/*
hotfix/*
30. Documentação do Código

Utilizar:

PHPDoc.

Exemplo:

/**
* Calcula lucro da venda
*
* @param Sale $sale
* @return float
*/
31. Configuração Inicial do Projeto

Primeira instalação:

composer install

cp .env.example .env

php migrate

php seed
32. Critérios de Aceitação
[ ] MVC implementado

[ ] Services separados

[ ] Repository Pattern

[ ] Composer configurado

[ ] Autoload PSR-4

[ ] Middleware funcionando

[ ] Multi-tenant aplicado

[ ] Segurança básica

[ ] Logs configurados

[ ] API preparada
Encerramento da Parte 16

A arquitetura definida garante que o ImportControl seja desenvolvido como um produto SaaS profissional.

O sistema terá:

código organizado;
baixo acoplamento;
facilidade de evolução;
segurança;
possibilidade de crescimento para milhares de usuários.