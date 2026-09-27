# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 26 — Arquitetura Técnica Final, Estrutura MVC, Clean Code e Organização do Projeto

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Arquitetura de software, padrões técnicos e organização do código

---

# 1. Objetivo

Esta parte define a arquitetura técnica do ImportControl, estabelecendo padrões obrigatórios para garantir:

- código organizado;
- fácil manutenção;
- escalabilidade;
- segurança;
- separação de responsabilidades;
- possibilidade de evolução para SaaS completo.

O projeto deverá seguir princípios de:

- MVC;
- Clean Code;
- SOLID;
- arquitetura em camadas;
- orientação a objetos;
- boas práticas PHP 8+.

---

# 2. Stack Tecnológica Oficial

## Backend


PHP 8.2+


Responsável por:

- regras de negócio;
- autenticação;
- APIs;
- processamento;
- comunicação com banco.

---

## Banco de Dados


MariaDB 10+
ou
MySQL 8+


Características:

- relacional;
- normalizado;
- preparado para multiempresa;
- com histórico e auditoria.

---

## Front-end

Tecnologias:


HTML5

CSS3

JavaScript ES6+

Bootstrap 5

jQuery

AJAX


---

## Bibliotecas

Utilizar:


Bootstrap

DataTables

Chart.js

SweetAlert2

jQuery Mask

CKEditor

mPDF


---

# 3. Arquitetura Geral

O sistema seguirá arquitetura em camadas:


Usuário

↓

Interface Web

↓

Controllers

↓

Services

↓

Repositories

↓

Models

↓

Database


---

# 4. Padrão MVC

O sistema deverá utilizar:


Model

View

Controller


---

## Model

Responsável por:

- representar entidades;
- regras simples de dados;
- relacionamento.

Exemplo:


Product.php

Sale.php

Customer.php


---

## View

Responsável por:

- HTML;
- componentes visuais;
- apresentação.

Exemplo:


products/index.php

sales/create.php


---

## Controller

Responsável por:

- receber requisições;
- validar entrada;
- chamar serviços;
- retornar respostas.

Exemplo:


ProductController.php


---

# 5. Estrutura de Pastas

Estrutura recomendada:


/app

/Controllers

/Models

/Services

/Repositories

/Middleware

/Validators

/Helpers

/Exceptions

/config

database.php

app.php

/core

Router.php

Controller.php

Database.php

Session.php

/public

index.php

/assets

    /css

    /js

    /images

/resources

/views

    /layouts

    /products

    /sales

    /dashboard

/storage

/uploads

/logs

/database

/migrations

/seeders

/routes

web.php

api.php

/vendor


---

# 6. Responsabilidade das Camadas

## Controller

Nunca deverá conter:

- regras complexas;
- cálculos;
- consultas SQL.

---

Exemplo errado:

```php
$sql = "SELECT * FROM products";

Exemplo correto:

$productService->list();
7. Camada Service

Responsável pelas regras do negócio.

Exemplo:

Venda realizada

↓

Verificar estoque

↓

Calcular lucro

↓

Registrar financeiro

↓

Atualizar estoque

Classes:

SaleService

StockService

FinancialService
8. Camada Repository

Responsável pelo acesso aos dados.

Exemplo:

ProductRepository

Métodos:

findAll()

findById()

save()

update()

delete()

Benefícios:

consultas centralizadas;
fácil troca de banco;
testes simplificados.
9. Models

Os Models representam entidades.

Exemplo:

class Product
{

private int $id;

private string $name;

private float $cost;

}

Entidades principais:

User

Tenant

Product

Category

Supplier

Customer

Sale

Trip

Expense

Transaction
10. Princípios SOLID

O projeto deverá seguir:

S — Single Responsibility

Cada classe possui uma única responsabilidade.

Exemplo:

Errado:

SaleController

faz venda

faz PDF

faz email

faz financeiro

Correto:

SaleController

SaleService

PdfService

EmailService
O — Open Closed

Código aberto para extensão e fechado para alteração.

L — Liskov

Classes filhas devem substituir classes pais.

I — Interface Segregation

Interfaces pequenas e específicas.

D — Dependency Inversion

Depender de abstrações.

11. Autoload PSR-4

Utilizar Composer.

Arquivo:

composer.json

Exemplo:

{
"autoload": {

"psr-4": {

"App\\": "app/"

}

}
}

Após alteração:

composer dump-autoload
12. Sistema de Rotas

Criar Router próprio.

Exemplo:

Route::get(
'/products',
'ProductController@index'
);

Rotas separadas:

web.php

api.php
13. Controle de Sessão

Criar:

SessionManager

Responsável:

login;
logout;
usuário atual;
permissões.
14. Autenticação

Fluxo:

Login

↓

Validar usuário

↓

Criar sessão

↓

Carregar permissões

↓

Liberar sistema
15. Controle de Permissões

Modelo:

Usuário

↓

Perfil

↓

Permissões

Exemplo:

Usuário comum:

Venda

Produto

Cliente

Desenvolvedor:

Usuários

Empresas

Configurações

Logs
16. Middleware

Criar camadas de proteção:

AuthMiddleware

PermissionMiddleware

TenantMiddleware

Exemplo:

if(!$user){

redirect('/login');

}
17. Arquitetura SaaS Multi-Tenant

O sistema deverá nascer preparado para múltiplas empresas.

Modelo:

Empresa A

Empresa B

Empresa C

Todas tabelas principais deverão possuir:

tenant_id

Exemplo:

Tabela:

products

Campos:

id

tenant_id

name
18. Isolamento de Dados

Regra:

Um usuário nunca poderá acessar dados de outro tenant.

Todas consultas:

WHERE tenant_id = ?
19. Banco de Dados

Utilizar:

migrations;
foreign keys;
índices;
constraints.

Exemplo:

FOREIGN KEY(category_id)
REFERENCES categories(id)
20. Migrations

Toda alteração estrutural deverá gerar arquivo:

Exemplo:

20260801_create_products_table.sql

Nunca alterar banco manualmente em produção.

21. Seeders

Criar dados iniciais:

Exemplo:

Categorias padrão

Permissões

Usuário administrador
22. Validação de Dados

Criar camada:

Validators

Exemplo:

ProductValidator

SaleValidator

UserValidator

Validar:

campos obrigatórios;
formatos;
valores;
permissões.
23. Tratamento de Erros

Criar:

Exceptions

Nunca exibir erro SQL ao usuário.

Exemplo:

Usuário:

Erro ao salvar produto.

Log:

SQL Exception
Data
Usuário
24. Sistema de Logs

Registrar:

Login

Alterações

Erros

Ações importantes

Tabela:

system_logs

Campos:

id

tenant_id

user_id

action

description

ip

created_at
25. Segurança

Implementar:

SQL Injection

Utilizar:

PDO Prepared Statements
XSS

Escapar saída:

htmlspecialchars()
CSRF

Criar:

token de formulário
Senhas

Utilizar:

password_hash()
26. Upload de Arquivos

Arquivos:

Notas

Comprovantes

Imagens

Regras:

validar extensão;
limitar tamanho;
renomear arquivo;
armazenar fora da pasta pública.
27. API REST Preparada

Mesmo sendo sistema web, preparar APIs.

Estrutura:

/api/v1

Exemplo:

GET

/api/v1/products

Resposta:

{
"success":true,
"data":[]
}
28. Configuração por Ambiente

Criar:

.env

Exemplo:

APP_ENV=production

DB_HOST=

DB_DATABASE=

DB_USERNAME=

DB_PASSWORD=

Nunca armazenar senha no código.

29. Controle de Versão

Utilizar:

Git

Branches:

main

develop

feature/*
bugfix/*
30. Padrão de Commits

Utilizar:

feat:
fix:
refactor:
docs:
security:

Exemplo:

feat: adiciona módulo financeiro
31. Testes

Preparar estrutura para:

PHPUnit

Testar:

serviços;
regras financeiras;
cálculos;
permissões.
32. Documentação Interna

Cada módulo deverá possuir:

README.md

Documentação técnica

Fluxos
33. Desenvolvimento com GitHub Copilot

O Copilot deverá receber instruções:

Sempre:

Utilize PHP 8+

Siga MVC

Não criar SQL no Controller

Utilize Services

Utilize Repository

Código limpo

Comentários apenas quando necessários
34. Ordem de Desenvolvimento Recomendada
Fase 1

Base do sistema:

Autenticação

Usuários

Tenant

Permissões
Fase 2

Cadastros:

Produtos

Clientes

Fornecedores
Fase 3

Operação:

Viagens

Compras

Estoque

Vendas
Fase 4

Gestão:

Financeiro

Dashboard

Relatórios
Fase 5

SaaS:

Planos

Assinaturas

Pagamentos

Painel administrador
35. Critérios de Aceitação
[ ] Arquitetura MVC implementada

[ ] Código separado por responsabilidade

[ ] Services criados

[ ] Repository implementado

[ ] Banco com migrations

[ ] Multi-tenant preparado

[ ] Segurança aplicada

[ ] Logs funcionando

[ ] Controle de permissões

[ ] Código compatível PHP 8+
Encerramento da Parte 26

Esta arquitetura define a base técnica do ImportControl.

Seguindo este padrão, o sistema poderá evoluir de um sistema individual para uma plataforma SaaS profissional, mantendo:

Organização

Segurança

Escalabilidade

Manutenção simples

Evolução contínua