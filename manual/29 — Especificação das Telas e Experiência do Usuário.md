# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 29 — Especificação das Telas e Experiência do Usuário (UX/UI)

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Definição visual, navegação, telas, componentes e experiência do usuário

---

# 1. Objetivo

Esta parte define a experiência visual e funcional do ImportControl.

O objetivo é criar uma interface:

- moderna;
- profissional;
- limpa;
- intuitiva;
- responsiva;
- semelhante a sistemas SaaS atuais.

A interface deverá transmitir:


Confiança

Organização

Controle

Profissionalismo


---

# 2. Identidade Visual

## Conceito

O sistema representa:

- comércio internacional;
- gestão financeira;
- controle empresarial.

A identidade deverá combinar:


Tecnologia

Segurança

Negócios

Eficiência


---

# 3. Paleta de Cores

## Cor principal

Azul

Uso:

- menus;
- botões principais;
- cabeçalhos;
- identidade.

Exemplo:


#0D47A1


---

## Cor secundária

Vermelho

Uso:

- alertas;
- ações importantes;
- indicadores negativos.

Exemplo:


#D32F2F


---

## Cor neutra

Branco

Uso:

- fundos;
- cards;
- áreas de conteúdo.

Exemplo:


#FFFFFF


---

## Cores auxiliares

Verde:


Sucesso

Lucros

Pagamentos realizados


Amarelo:


Avisos

Pendências


Cinza:


Textos secundários

Bordas


---

# 4. Tipografia

Utilizar:


Roboto

Inter

Open Sans


---

Hierarquia:

## Título


24px - 32px


---

## Subtítulo


18px - 22px


---

## Texto


14px - 16px


---

# 5. Estrutura Geral da Interface

Layout principal:


LOGO

Menu lateral

Dashboard

Conteúdo

Rodapé


---

# 6. Menu Lateral

Menu fixo:


Dashboard

Produtos

Estoque

Clientes

Fornecedores

Viagens

Compras

Vendas

Financeiro

Relatórios

Configurações


---

# 7. Menu do Administrador SaaS

Somente desenvolvedor:


Empresas

Usuários

Planos

Assinaturas

Logs

Configurações globais


---

# 8. Barra Superior

Componentes:


Logo

Nome empresa

Busca

Notificações

Usuário

Sair


---

Exemplo:


ImportControl

Empresa XYZ

🔔

João


---

# 9. Dashboard Principal

Tela inicial.

---

## Estrutura:


Olá, João!

Resumo do negócio

[ Faturamento ]

[ Lucro ]

[ Estoque ]

[ Clientes ]

Gráficos


---

# 10. Cards de Indicadores

Componente padrão:


Título

Valor

Indicador

Ícone


---

Exemplo:


Faturamento

R$120.000

↑ 15%


---

# 11. Tela de Login

Objetivo:

Primeira impressão do sistema.

---

Layout:


Logo

Email

Senha

[ Entrar ]

Esqueci senha


---

Características:

- fundo limpo;
- imagem discreta;
- foco no acesso.

---

# 12. Cadastro de Empresa

Tela SaaS inicial.

Campos:


Nome empresa

CNPJ

Email

Telefone

Senha administrador


---

Após cadastro:


Criar ambiente da empresa


---

# 13. Tela de Produtos

## Objetivo

Gerenciar catálogo.

---

Layout:


Produtos

[ Novo Produto ]

Pesquisa

Tabela

Código

Nome

Categoria

Custo

Venda

Estoque

Ações


---

# 14. Cadastro de Produto

Formulário:


Nome

Categoria

Fornecedor

Código

Descrição

Preço custo USD

Cotação

Preço custo BRL

Preço venda

Estoque mínimo

Imagem


---

# 15. Conversão de Moeda

Componente:


Preço USD

$100

Cotação

5,20

Resultado

R$520


---

# 16. Tela de Estoque

Mostrar:


Produto

Quantidade

Valor estoque

Status


---

Status:

Verde:


Normal


Amarelo:


Baixo estoque


Vermelho:


Sem estoque


---

# 17. Tela de Clientes

Tabela:


Nome

Telefone

Compras

Última compra

Valor total

Ações


---

# 18. Tela de Viagens

Dashboard:


Viagens

[ Nova viagem ]

Miami 2026

Status

Investimento

Lucro


---

# 19. Cadastro de Viagem

Campos:


Nome

Destino

Data saída

Data retorno

Moeda utilizada

Cotação


---

# 20. Despesas de Viagem

Tela:


Hospedagem

Passagem

Alimentação

Transporte

Outros


---

Cada item:


Descrição

Valor moeda origem

Conversão

Valor real


---

# 21. Tela de Compras

Mostrar:


Compra

Fornecedor

Viagem

Valor dólar

Valor real

Produtos


---

# 22. Tela de Vendas

Objetivo:

Venda rápida.

---

Layout:


Buscar produto

Produto

Quantidade

Preço

Subtotal

Desconto

Total

[Finalizar]


---

# 23. Alteração de Preço na Venda

Permitir:


Preço padrão

R$500


Alteração:


Novo preço

R$450


---

Registrar:


Preço original

Preço vendido

Usuário responsável


---

# 24. Tela Financeira

Dashboard:


Saldo atual

Receitas

Despesas

Lucro

Contas pendentes


---

# 25. Fluxo de Caixa

Visual:


Data

Descrição

Entrada

Saída

Saldo


---

# 26. Tela de Relatórios

Menu:


Relatório vendas

Relatório estoque

Relatório financeiro

Relatório viagens

Relatório produtos


---

Filtros:


Período

Categoria

Produto

Cliente


---

# 27. Componentes Reutilizáveis

Criar biblioteca interna:


Card

Modal

Tabela

Formulário

Botão

Alert

Badge

Dropdown

Navbar


---

# 28. Tabelas

Padrão:

Utilizar:


DataTables


Recursos:

- busca;
- paginação;
- ordenação;
- exportação.

---

# 29. Formulários

Padrão:


Label

Campo

Mensagem erro

Ajuda


---

Exemplo:


Preço venda

[________]

Informe o valor comercial.


---

# 30. Modais

Utilizar:


Bootstrap Modal


Para:

- confirmação;
- edição rápida;
- detalhes.

---

# 31. Alertas

Utilizar:


SweetAlert2


Exemplos:

Sucesso:


Produto salvo.


Erro:


Não foi possível salvar.


Confirmação:


Deseja excluir?


---

# 32. Responsividade

Obrigatório:


Desktop

Notebook

Tablet

Celular


---

Bootstrap:

Utilizar:


container-fluid

row

col-md

col-lg


---

# 33. Experiência Mobile

Preparar para futura aplicação.

Priorizar:

- botões grandes;
- tabelas adaptáveis;
- navegação simples.

---

# 34. Ícones

Utilizar:


Bootstrap Icons

Font Awesome


---

Exemplos:

Produto:


📦


Financeiro:


💰


Viagem:


✈️


---

# 35. Busca Global

Criar campo:


Pesquisar...


Buscar:


Produtos

Clientes

Vendas

Documentos


---

# 36. Notificações

Sistema deverá informar:

Exemplos:


Produto abaixo estoque

Pagamento atrasado

Nova venda

Erro financeiro


---

# 37. Estados Vazios

Nunca deixar tela vazia.

Exemplo:

Sem produtos:


Nenhum produto cadastrado.

[Adicionar produto]


---

# 38. Loading

Utilizar:


Spinner Bootstrap


Durante:

- consultas;
- gravações;
- relatórios.

---

# 39. Acessibilidade

Implementar:

- contraste adequado;
- labels corretas;
- navegação teclado;
- textos claros.

---

# 40. Padrão Visual Final

O sistema deverá transmitir:


SaaS Premium

ERP moderno

Gestão empresarial

Facilidade de uso


---

# 41. Critérios de Aceitação


[ ] Layout responsivo

[ ] Identidade azul/vermelho/branco

[ ] Componentes padronizados

[ ] Navegação intuitiva

[ ] Dashboard moderno

[ ] Formulários consistentes

[ ] Boa experiência mobile

[ ] Interface SaaS profissional


---

# Encerramento da Parte 29

A interface definida transforma o ImportControl em um produto com aparência profissional.

A experiência deve ser:


Simples para o vendedor

Completa para o gestor

Poderosa para o administrador


O usuário deverá conseguir administrar todo o negócio sem necessidade de conhecimento técnico.

---

## Próxima Parte (Parte 30)

# Especificação dos Módulos de Usuário, Permissões e Controle de Acesso

Será detalhado:

- usuários;
- perfis;
- permissões;
- regras de segurança;
- telas administrativas;
- auditoria;
- controle SaaS.