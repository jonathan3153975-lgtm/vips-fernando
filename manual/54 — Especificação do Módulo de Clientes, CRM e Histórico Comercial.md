# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 54 — Especificação do Módulo de Clientes, CRM e Histórico Comercial

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Gestão de clientes, relacionamento comercial, histórico de compras, segmentação e fidelização

---

# 1. Objetivo

O módulo de Clientes será responsável por centralizar todas as informações dos compradores e transformar o sistema em uma ferramenta de relacionamento comercial.

O objetivo é permitir que o proprietário acompanhe:


Quem são seus clientes

O que compraram

Quanto gastaram

Quando compraram

Qual frequência de compra

Quais produtos preferem

Qual potencial de retorno


---

# 2. Conceito Geral

O fluxo comercial será:


Cadastro Cliente

    ↓

Primeira venda

    ↓

Histórico de compras

    ↓

Relacionamento

    ↓

Fidelização

    ↓

Novas oportunidades


---

# 3. Requisitos Funcionais

O módulo deverá permitir:


RF001 - Cadastrar clientes

RF002 - Editar clientes

RF003 - Consultar histórico

RF004 - Registrar contatos

RF005 - Classificar clientes

RF006 - Identificar clientes recorrentes

RF007 - Gerar indicadores

RF008 - Integrar vendas

RF009 - Controlar observações

RF010 - Exportar dados


---

# 4. Tipos de Clientes

O sistema deverá permitir classificar:


Pessoa Física

Pessoa Jurídica

Cliente VIP

Cliente eventual

Revendedor

Parceiro comercial


---

# 5. Cadastro de Cliente

Informações básicas:


Nome completo

Nome fantasia

CPF/CNPJ

Telefone

WhatsApp

Email

Data nascimento

Endereço

Cidade

Estado

Observações


---

# 6. Tabela Customers

```sql
CREATE TABLE customers (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

tenant_id BIGINT NOT NULL,

type ENUM(
'INDIVIDUAL',
'COMPANY'
),

name VARCHAR(150) NOT NULL,

document VARCHAR(30),

trade_name VARCHAR(150),

email VARCHAR(150),

phone VARCHAR(30),

whatsapp VARCHAR(30),

birth_date DATE,

status ENUM(
'ACTIVE',
'INACTIVE'
),

notes TEXT,

created_at TIMESTAMP,

updated_at TIMESTAMP

);
7. Endereço do Cliente

Separar endereço em tabela própria.

Tabela:

customer_addresses

Campos:

CREATE TABLE customer_addresses (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

customer_id BIGINT,

street VARCHAR(150),

number VARCHAR(20),

complement VARCHAR(100),

district VARCHAR(100),

city VARCHAR(100),

state VARCHAR(50),

zipcode VARCHAR(15),

type VARCHAR(30)

);
8. Histórico Comercial

O cliente deverá possuir:

Total comprado

Quantidade compras

Última compra

Ticket médio

Produtos favoritos

Valor gerado
9. Integração com Vendas

Quando uma venda ocorrer:

Venda criada

↓

Cliente vinculado

↓

Histórico atualizado

↓

Indicadores recalculados
10. Perfil Comercial do Cliente

Criar classificação:

Novo

Ativo

Recorrente

VIP

Inativo

Perdido
11. Critérios de Classificação

Exemplo:

Novo
Primeira compra realizada
Recorrente
Comprou mais de X vezes
VIP
Maior volume financeiro
Inativo
Sem compras por período definido
12. Segmentação de Clientes

Permitir filtros:

Cidade

Estado

Valor gasto

Quantidade compras

Última compra

Produto comprado

Categoria preferida
13. Tags de Clientes

Permitir etiquetas:

Exemplo:

Cliente VIP

Compra eletrônicos

Compra à vista

Revendedor

Interessado iPhone

Tabela:

customer_tags
14. Relacionamento Cliente x Tags

Tabela:

customer_tag_relation

Estrutura:

Cliente

N:N

Tags
15. Histórico de Contatos

Registrar:

Ligação

Mensagem

WhatsApp

Email

Observação

Visita

Tabela:

customer_interactions

Estrutura:

CREATE TABLE customer_interactions (

id BIGINT AUTO_INCREMENT PRIMARY KEY,

customer_id BIGINT,

user_id BIGINT,

type VARCHAR(50),

description TEXT,

interaction_date DATETIME,

created_at TIMESTAMP

);
16. Linha do Tempo do Cliente

Tela deverá mostrar:

---------------------------------

João Silva


05/08/2026

Comprou iPhone


20/08/2026

Contato WhatsApp


01/09/2026

Nova compra


---------------------------------
17. Cadastro de Preferências

Registrar:

Produtos de interesse

Marcas favoritas

Faixa de preço

Forma pagamento preferida
18. Produtos Favoritos

Calcular automaticamente:

Produto mais comprado

Categoria mais comprada

Valor médio gasto
19. Ticket Médio

Fórmula:

Ticket médio

=

Valor total comprado

/

Quantidade de compras

Exemplo:

Compras:

R$10.000


Quantidade:

5


Ticket médio:

R$2.000
20. Valor do Cliente (Lifetime Value)

Calcular:

LTV

=

Valor total comprado

-
Custos relacionados

Objetivo:

Identificar:

Clientes mais importantes
21. Ranking de Clientes

Criar relatório:

Top clientes por faturamento

Top clientes por frequência

Top clientes por lucro
22. Dashboard Clientes

Exibir:

Quantidade clientes

Clientes ativos

Clientes novos

Clientes inativos

Maior comprador

Ticket médio geral
23. Alertas Comerciais

Criar alertas:

Cliente sem comprar há 60 dias

Cliente VIP sem contato

Cliente interessado sem venda

Aniversário próximo
24. Cadastro de Observações

Permitir:

Informações pessoais

Preferências

Histórico negociação

Anotações internas
25. Controle de Privacidade

Dados sensíveis:

CPF

CNPJ

Telefone

Email

Devem possuir:

Controle de acesso

Registro de visualização

Proteção no banco
26. Importação de Clientes

Preparar:

CSV

Excel

Planilhas externas

Processo:

Arquivo enviado

↓

Validação

↓

Prévia

↓

Importação
27. Exportação de Clientes

Permitir:

CSV

Excel

PDF

Filtros:

Clientes ativos

VIP

Cidade

Período
28. Comunicação Futura

Preparar integração:

WhatsApp Business API

Email marketing

SMS

Notificações
29. Campanhas Comerciais

Preparar módulo futuro:

Criar campanha

Selecionar clientes

Enviar mensagem

Registrar retorno
30. Histórico de Compras

Tela:

Cliente

↓

Compras realizadas


Venda 001

Data

Produtos

Valor


Venda 002

Data

Produtos

Valor
31. Consulta Rápida na Venda

Durante uma venda:

Ao selecionar cliente:

Mostrar:

Última compra

Produtos comprados

Preferências

Limite desconto

Observações
32. Controle de Limite Comercial

Preparar:

Limite de compra

Crédito disponível

Débitos pendentes

Tabela futura:

customer_credit
33. Integração Financeira

Permitir visualizar:

Compras realizadas

Valores pagos

Valores pendentes

Histórico financeiro
34. Serviços Backend

Criar:

CustomerService

CustomerHistoryService

CustomerSegmentService

CustomerReportService

CustomerInteractionService
35. Controllers

Criar:

CustomerController

CustomerInteractionController

CustomerReportController
36. API Clientes

Endpoints:

GET    /api/v1/customers

POST   /api/v1/customers

PUT    /api/v1/customers/{id}

DELETE /api/v1/customers/{id}


GET /api/v1/customers/{id}/history

GET /api/v1/customers/{id}/purchases
37. Permissões

Criar:

customers.view

customers.create

customers.edit

customers.delete

customers.export

customers.history
38. Auditoria

Registrar:

Cadastro cliente

Alteração dados

Exclusão

Visualização dados sensíveis

Exportação
39. Regras de Negócio
Regra 1

Cliente utilizado em venda nunca deve ser apagado.

Regra 2

Alterações cadastrais devem gerar histórico.

Regra 3

Dados sensíveis devem respeitar permissões.

Regra 4

Histórico comercial nunca deve ser removido.

40. Critérios de Aceitação
[ ] Cadastro clientes funcionando

[ ] Histórico compras funcionando

[ ] Segmentação funcionando

[ ] Tags funcionando

[ ] Linha do tempo criada

[ ] Indicadores funcionando

[ ] Ranking clientes

[ ] Integração vendas

[ ] Exportação funcionando

[ ] Auditoria aplicada

[ ] Permissões aplicadas
Encerramento da Parte 54

O módulo de Clientes transformará o ImportControl de um sistema de controle de vendas em uma ferramenta de relacionamento comercial.

O proprietário poderá entender:

Quem compra mais?

Quem gera mais lucro?

Quem precisa ser reconquistado?

Quem merece atendimento especial?

Quais produtos têm maior aceitação?

A arquitetura ficará preparada para evolução futura em CRM completo.