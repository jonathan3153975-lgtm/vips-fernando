PARTE 1 — PRODUCT REQUIREMENTS DOCUMENT (PRD)
Sistema ImportControl

Versão: 1.0
Status: Em desenvolvimento
Tipo: SaaS Web Application
Tecnologias: PHP 8.3+, MariaDB, JavaScript ES6, Bootstrap 5, HTML5, CSS3

1. Visão Geral
1.1 Objetivo

O ImportControl é uma plataforma SaaS destinada ao gerenciamento completo de pequenos e médios importadores que realizam viagens internacionais para aquisição de mercadorias destinadas à revenda no Brasil.

O sistema deverá controlar todas as etapas do processo comercial, desde o planejamento da viagem até a venda do último item adquirido, permitindo ao usuário conhecer com precisão:

custo real de cada produto;
despesas da viagem;
lucro de cada venda;
lucro por viagem;
lucro mensal;
fluxo de caixa;
estoque disponível;
indicadores financeiros e comerciais.

O projeto deve ser concebido desde o início como um produto comercial escalável, preparado para atender múltiplas empresas utilizando uma única infraestrutura (arquitetura SaaS Multi-Tenant).

2. Objetivos do Produto
Objetivo Principal

Disponibilizar uma solução que permita ao importador administrar seu negócio com segurança, rapidez e precisão financeira.

Objetivos Secundários
eliminar controles em planilhas;
centralizar todas as informações;
calcular automaticamente custos e lucros;
fornecer indicadores para tomada de decisão;
reduzir erros operacionais;
permitir crescimento para múltiplas empresas.
3. Público-Alvo

O sistema foi concebido para atender:

Importadores Independentes

Pessoas que viajam periodicamente ao exterior para comprar mercadorias.

Pequenos Comerciantes

Lojas físicas ou virtuais que trabalham com produtos importados.

Revendedores

Usuários que realizam compras em viagens internacionais para posterior revenda.

Distribuidores

Empresas que desejam controlar custos por lote e viagem.

4. Problemas que o Sistema Resolve

Atualmente, muitos importadores utilizam planilhas para controlar suas operações. Esse modelo apresenta diversas limitações:

dificuldade em calcular o custo real dos produtos;
ausência de controle de estoque por lote;
falta de rastreabilidade das despesas;
desconhecimento da margem de lucro real;
dificuldade em localizar produtos;
ausência de histórico financeiro consolidado;
baixa confiabilidade das informações.

O ImportControl elimina essas limitações por meio de um sistema integrado.

5. Conceito Central do Sistema

Todo o sistema gira em torno de um único elemento:

A VIAGEM

A viagem representa um ciclo completo de importação.

Toda informação do sistema deriva desse ciclo.

Cada viagem possui:

despesas;
cotações;
fornecedores;
compras;
produtos;
lotes;
documentos;
fotos;
observações.

Após o retorno da viagem:

os produtos entram em estoque;
ficam disponíveis para venda;
geram receitas;
produzem indicadores financeiros.
6. Fluxo Macro do Negócio
Planejamento da viagem
        │
        ▼
Cadastro da viagem
        │
        ▼
Cadastro da cotação da moeda
        │
        ▼
Cadastro das despesas
        │
        ▼
Cadastro dos fornecedores
        │
        ▼
Cadastro das compras
        │
        ▼
Geração automática dos lotes
        │
        ▼
Entrada no estoque
        │
        ▼
Rateio das despesas
        │
        ▼
Cálculo do custo real
        │
        ▼
Definição do preço sugerido
        │
        ▼
Venda
        │
        ▼
Recebimento
        │
        ▼
Fluxo de Caixa
        │
        ▼
Relatórios
        │
        ▼
Indicadores

7. Filosofia do Projeto

O sistema deverá priorizar:

Simplicidade

Operações simples.

Poucos cliques.

Fluxos intuitivos.

Performance

Todas as telas deverão carregar rapidamente.

As consultas deverão ser otimizadas.

Índices deverão ser utilizados em todas as tabelas críticas.

Escalabilidade

Toda arquitetura deverá permitir crescimento sem necessidade de refatoração.

O sistema deverá suportar:

milhares de produtos;
centenas de viagens;
milhões de vendas;
milhares de clientes.
Segurança

Nenhuma informação poderá ser acessada por outro tenant.

Toda consulta deverá respeitar o contexto da empresa autenticada.

Usabilidade

O usuário deverá conseguir aprender o sistema praticamente sem treinamento.

A interface deverá transmitir sensação de organização e confiabilidade.

8. Funcionalidades Principais
Gestão de Viagens

Cada viagem será considerada um projeto financeiro independente.

Ela concentrará:

despesas;
compras;
fornecedores;
documentos;
cotação utilizada.

Ao final, o sistema produzirá automaticamente uma DRE da viagem.

Gestão de Produtos

Cada produto será vinculado a:

lote;
fornecedor;
viagem;
categoria;
marca.

O sistema calculará automaticamente:

custo em moeda estrangeira;
custo convertido;
custo rateado;
custo final.
Gestão de Estoque

O estoque será controlado por lote.

Cada entrada e saída gerará movimentações permanentes.

Nenhuma movimentação poderá ser excluída.

Gestão Comercial

O usuário poderá:

cadastrar clientes;
realizar vendas;
aplicar descontos;
registrar múltiplas formas de pagamento;
emitir comprovantes.
Gestão Financeira

Controlará:

contas a receber;
contas pagas;
despesas;
receitas;
saldo;
fluxo de caixa.
Business Intelligence

O sistema fornecerá indicadores estratégicos.

Exemplos:

margem líquida;
ROI;
markup;
ticket médio;
produtos mais lucrativos;
produtos menos lucrativos;
giro de estoque.
9. Princípios Arquiteturais

Desde sua primeira versão, o sistema deverá seguir:

Clean Architecture (adaptada ao MVC)
Clean Code
SOLID
DRY
KISS
PSR-12
Repository Pattern
Service Layer
DTO
Dependency Injection
Soft Delete
Versionamento de Banco de Dados (Migrations)
API First
10. Arquitetura SaaS

Embora inicialmente exista apenas um cliente, todo o sistema será desenvolvido como uma plataforma SaaS.

Cada empresa possuirá:

usuários próprios;
estoque próprio;
viagens próprias;
clientes próprios;
fornecedores próprios;
configurações próprias.

O isolamento ocorrerá por meio do identificador tenant_id, presente em todas as tabelas de negócio.

Essa abordagem permitirá a expansão futura para centenas ou milhares de empresas utilizando a mesma base de código.

11. Visão Estratégica

O ImportControl não deve ser tratado como um sistema interno, mas como um produto de software.

Toda decisão arquitetural deverá considerar:

facilidade de manutenção;
escalabilidade;
reutilização de componentes;
baixo acoplamento;
alta coesão;
possibilidade de integração futura com aplicativos móveis, APIs externas e marketplaces.
12. Critérios de Qualidade

O projeto deverá atender aos seguintes requisitos mínimos:

Código padronizado conforme PSR-12.
Separação clara entre camadas (Controller, Service, Repository, Model e View).
Ausência de regras de negócio nas Views.
Ausência de SQL nas Controllers.
Cobertura por logs de operações críticas.
Preparação para testes automatizados.
Interface responsiva para desktop, tablet e smartphone.
Alto desempenho mesmo com grandes volumes de dados.
Facilidade de evolução sem necessidade de reestruturação da arquitetura.
Encerramento da Parte 1

Este documento estabelece a visão estratégica e os princípios fundamentais do ImportControl. As próximas partes detalharão a arquitetura técnica, o modelo de dados, os módulos, as regras de negócio e todos os componentes necessários para a implementação completa do sistema.
