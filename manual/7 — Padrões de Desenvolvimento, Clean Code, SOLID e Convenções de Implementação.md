# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 7 — Padrões de Desenvolvimento, Clean Code, SOLID e Convenções de Implementação

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Guia técnico obrigatório de desenvolvimento

---

# 1. Objetivo

Esta seção define os padrões obrigatórios de desenvolvimento do ImportControl.

O objetivo é garantir que o código produzido seja:

- legível;
- previsível;
- seguro;
- testável;
- escalável;
- fácil de manter.

Todos os desenvolvedores e ferramentas de IA utilizadas no projeto deverão seguir estas regras.

---

# 2. Princípios Fundamentais

O desenvolvimento deverá seguir:

- Clean Code;
- SOLID;
- DRY;
- KISS;
- YAGNI;
- Separation of Concerns;
- High Cohesion / Low Coupling.

---

# 3. Regra Principal

> Código deve ser escrito para humanos entenderem, máquinas apenas executam.

A prioridade não é escrever menos linhas.

A prioridade é escrever código compreensível.

---

# 4. Clean Code

## 4.1 Nomes Significativos

Variáveis, métodos e classes devem revelar sua intenção.

Errado:

```php
$x = 500;

Correto:

$productPurchasePrice = 500;

Errado:

calc();

Correto:

calculateProductRealCost();
5. Classes Pequenas

Uma classe deve possuir uma única responsabilidade.

Evitar:

ProductController

- salvar produto
- calcular custo
- enviar email
- gerar PDF
- atualizar estoque

Correto:

ProductController

↓

ProductService

↓

StockService

↓

ReportService
6. Métodos Pequenos

Métodos devem executar uma tarefa.

Evitar:

createSale()
{
    validar cliente;
    calcular estoque;
    baixar estoque;
    gerar financeiro;
    enviar email;
    criar relatório;
}

Correto:

createSale()
{
    validateSale();

    registerSale();

    updateStock();

    generateFinancialEntry();

}
7. Evitar Comentários Desnecessários

Código ruim:

// soma valor ao total
$total = $value + $total;

O código já explica.

Comentários devem explicar decisões.

Exemplo:

// Utiliza cotação congelada da data da compra.
// Nunca deve utilizar cotação atual,
// pois altera o custo histórico do produto.
8. Funções Puras

Sempre que possível utilizar funções sem efeitos colaterais.

Exemplo:

Bom:

calculateProfit(
    $salePrice,
    $cost
)

Retorna apenas o cálculo.

Evitar:

calculateProfit()
{
    alterar banco;
    enviar email;
    atualizar estoque;
}
9. SOLID

O sistema deverá seguir os cinco princípios SOLID.

10. S — Single Responsibility Principle
Princípio da Responsabilidade Única

Uma classe deve possuir apenas um motivo para mudar.

Errado:

class SaleManager
{

createSale()

sendEmail()

generatePdf()

calculateTax()

}

Correto:

SaleService

EmailService

PdfService

TaxService
11. O — Open/Closed Principle
Aberto para extensão, fechado para alteração

Novas funcionalidades devem ser adicionadas sem quebrar código existente.

Exemplo:

Formas de pagamento.

Errado:

if($payment=="PIX")

if($payment=="CARD")

if($payment=="CASH")

Correto:

Criar interface:

interface PaymentMethodInterface
{

pay();

}

Implementações:

PixPayment

CardPayment

CashPayment
12. L — Liskov Substitution Principle

Classes derivadas devem substituir suas classes base sem alterar comportamento.

Exemplo:

Todo pagamento deve obedecer:

PaymentInterface

Independentemente da implementação.

13. I — Interface Segregation Principle

Não criar interfaces gigantes.

Errado:

interface SystemInterface
{

login();

generateReport();

sendEmail();

uploadFile();

}

Correto:

AuthInterface

ReportInterface

EmailInterface

StorageInterface
14. D — Dependency Inversion Principle

Depender de abstrações.

Não depender de implementações concretas.

Errado:

class SaleService
{

private MysqlSaleRepository $repository;

}

Correto:

class SaleService
{

private SaleRepositoryInterface $repository;

}
15. DRY — Don't Repeat Yourself

Nunca duplicar regras.

Errado:

Cálculo de moeda:

TripController

SaleController

ReportController

Cada um faz sua conversão.

Correto:

CurrencyService

Centraliza.

16. KISS — Keep It Simple

Soluções devem ser simples.

Evitar:

abstrações desnecessárias;
frameworks internos complexos;
excesso de padrões.
17. YAGNI — You Aren't Gonna Need It

Não criar funcionalidades antes da necessidade.

Exemplo:

Não criar integração com 20 gateways antes de existir pagamento.

18. Tipagem Forte

Obrigatório:

declare(strict_types=1);

Sempre definir tipos:

Errado:

function save($data)

Correto:

function save(ProductDTO $data): Product
19. Retornos Explícitos

Evitar:

function calculate()
{

}

Preferir:

function calculate(): float
{

}
20. Null Safety

Evitar excesso de valores nulos.

Errado:

$product->price

Sem garantir existência.

Correto:

$product?->getPrice()
21. Tratamento de Exceções

Nunca utilizar:

catch(Exception $e)

sem tratamento.

Criar exceções específicas.

Exemplo:

InsufficientStockException

InvalidCurrencyException

UnauthorizedActionException
22. Exceções de Negócio

Exemplo:

Usuário tenta vender produto sem estoque.

Não é erro técnico.

É regra de negócio.

Criar:

StockUnavailableException
23. Logs

Operações importantes devem gerar logs.

Registrar:

usuário;
tenant;
ação;
data;
IP.

Exemplo:

Venda criada:

USER 15

TENANT 2

ACTION SALE_CREATED

VALUE R$ 5000
24. Níveis de Log

Utilizar:

DEBUG

INFO

WARNING

ERROR

CRITICAL
25. Auditoria Financeira

Nunca alterar informações financeiras sem histórico.

Exemplo:

Preço alterado:

Antes:

R$ 500

Depois:

R$ 450

Registrar:

usuário;
data;
motivo.
26. Transações de Banco

Operações críticas devem ser transacionais.

Exemplo:

Venda:

DB::transaction(function(){

createSale();

removeStock();

createFinancialEntry();

});

Se uma etapa falhar:

Tudo retorna ao estado anterior.

27. Segurança de Dados

Obrigatório:

Prepared Statements;
validação de entrada;
escape de saída;
proteção CSRF;
controle de sessão.
28. Nunca Confiar no Frontend

Validações JavaScript são apenas auxiliares.

Toda validação deve existir no backend.

29. SQL

Nunca escrever SQL dentro de:

Controllers;
Views;
Javascript.

Permitido:

Repository.

30. Queries

Sempre utilizar:

Prepared Statements.

Exemplo:

Correto:

$query = 
"SELECT *
 FROM products
 WHERE id = ?";

Nunca:

"SELECT *
FROM products
WHERE id=".$id;
31. Performance

Toda consulta deverá considerar:

índices;
paginação;
filtros;
limite de resultados.

Evitar:

Buscar 100 mil registros para mostrar 20.

32. Paginação

Obrigatória em:

produtos;
vendas;
clientes;
relatórios.
33. Cache

Utilizar cache para:

dashboards;
configurações;
permissões.

Nunca armazenar:

dados sensíveis;
senhas;
tokens.
34. Senhas

Nunca salvar senha pura.

Obrigatório:

password_hash()

Validação:

password_verify()
35. Segurança de Sessão

Implementar:

regeneração de ID;
expiração;
logout seguro;
proteção contra fixação.
36. Upload de Arquivos

Obrigatório validar:

extensão;
MIME;
tamanho;
nome seguro.

Nunca confiar:

$_FILES['file']['name']
37. Frontend

Javascript deve ser organizado.

Nunca:

<script>
500 linhas
</script>

Utilizar:

resources/js/modules
38. Componentização

Elementos repetidos devem ser componentes.

Exemplo:

Button

Modal

Table

Card

Input
39. CSS

Nunca utilizar estilos inline.

Errado:

<div style="color:red">

Correto:

<div class="alert-danger">
40. Commits

Padrão:

Conventional Commits.

Exemplos:

feat: add product registration

fix: correct stock calculation

refactor: improve sale service

docs: update PRD
41. Code Review

Toda funcionalidade importante deve ser revisada.

Avaliar:

arquitetura;
segurança;
performance;
testes.
42. Uso do GitHub Copilot

O Copilot deverá seguir estas regras:

Sempre:

respeitar arquitetura;
criar Services;
criar Repository;
utilizar DTO;
criar validações;
documentar métodos complexos.

Nunca:

colocar SQL em Controller;
criar lógica na View;
ignorar tenant_id;
criar código duplicado.
43. Checklist Antes de Finalizar Código

Antes de considerar uma tarefa concluída:

[ ] Segue SOLID

[ ] Possui validação

[ ] Possui tratamento de erro

[ ] Possui logs quando necessário

[ ] Respeita tenant

[ ] Possui testes

[ ] Não possui código duplicado

[ ] Não possui SQL fora do Repository

[ ] Está documentado
Encerramento da Parte 7

Esta seção estabelece o padrão de qualidade do código do ImportControl.

Qualquer código produzido deverá seguir estas regras para garantir que o sistema permaneça sustentável durante sua evolução.