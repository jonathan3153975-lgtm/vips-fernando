# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 10 — UX/UI Design System

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Identidade visual, componentes de interface e experiência do usuário

---

# 1. Objetivo

Esta seção define o padrão visual e de experiência do usuário do ImportControl.

O sistema deverá possuir uma interface:

- moderna;
- limpa;
- profissional;
- intuitiva;
- responsiva;
- rápida;
- agradável para uso diário.

O foco é criar uma experiência semelhante a sistemas SaaS modernos.

Referências de inspiração:

- dashboards financeiros;
- sistemas ERP modernos;
- plataformas SaaS B2B.

---

# 2. Conceito Visual

O ImportControl deverá transmitir:
Confiança

Controle

Organização

Tecnologia

Profissionalismo


A interface deverá evitar:

- excesso de informação;
- telas poluídas;
- cores exageradas;
- componentes antigos.

---

# 3. Identidade Visual

## Paleta Principal

A identidade deverá utilizar:

- azul;
- vermelho;
- branco;
- tons neutros.

---

# 4. Cores Oficiais

## Azul Principal

Representa:

- tecnologia;
- confiança;
- segurança.

Uso:

- botões principais;
- menus;
- destaques.

Exemplo:

```css
--primary-blue: #0D47A1;
Azul Secundário

Uso:

hover;
gráficos;
indicadores.
--secondary-blue: #1976D2;
Vermelho

Representa:

alertas;
ações críticas;
valores negativos.
--danger-red: #D32F2F;
Branco

Uso:

fundo;
cards;
áreas de conteúdo.
--white: #FFFFFF;
Cinzas

Para:

textos;
bordas;
divisores.

Exemplo:

--gray-100
--gray-200
--gray-500
--gray-700
5. Tipografia

Fonte recomendada:

Inter

Alternativas:

Roboto

Open Sans

Aplicação:

Títulos

Peso:

700

Subtítulos

Peso:

600

Texto normal

Peso:

400

6. Layout Geral

Estrutura principal:

------------------------------------------------

TOPBAR

------------------------------------------------

SIDEBAR | CONTEÚDO

        |

        |

------------------------------------------------

FOOTER

------------------------------------------------
7. Sidebar

Menu lateral fixo.

Características:

recolhível;
responsivo;
ícones;
indicação de página atual.

Estrutura:

Logo

Dashboard

Viagens

Produtos

Estoque

Vendas

Clientes

Financeiro

Relatórios

Configurações
8. Menu Developer

Quando usuário for Developer:

Dashboard

Tenants

Planos

Assinaturas

Usuários

Logs

Configurações
9. Topbar

Elementos:

Nome empresa

Cotação atual

Notificações

Perfil usuário

Logout
10. Dashboard Principal

O dashboard deverá apresentar informações rápidas.

Estrutura:

Cards indicadores

↓

Gráficos

↓

Últimas movimentações

↓

Alertas
11. Cards de Indicadores

Modelo:

--------------------------------

Produtos cadastrados

1.240

+12%

--------------------------------

Indicadores:

Produtos

Quantidade total.

Estoque

Valor atual.

Vendas

Total vendido.

Lucro

Lucro acumulado.

Viagens

Viagens abertas.

12. Gráficos

Biblioteca recomendada:

Chart.js

Tipos:

Linha

Evolução de vendas.

Barras

Comparativo mensal.

Pizza

Distribuição de despesas.

Área

Fluxo financeiro.

13. Componentes Bootstrap Customizados

O sistema utilizará:

Bootstrap 5+

com customização própria.

Estrutura:

resources/

css/

├── variables.css

├── components.css

├── layout.css

└── theme.css
14. Botões
Primário

Uso:

ações principais.

Exemplo:

Salvar Produto

Classe:

btn-primary
Secundário

Uso:

ações alternativas.

Danger

Uso:

exclusões.

Exemplo:

Excluir Venda
15. Cards

Todos os cards deverão possuir:

borda suave;
sombra discreta;
espaçamento interno;
cantos arredondados.

Exemplo:

border-radius: 12px;
16. Formulários

Padrão:

labels claras;
validação visual;
mensagens abaixo dos campos;
campos agrupados.

Exemplo:

Produto:

Nome

Categoria

Fornecedor

Preço

Quantidade

Foto
17. Campos Monetários

Sempre informar moeda.

Exemplo:

Preço compra

USD $
__________

Preço venda

BRL R$
__________
18. Campos com Conversão

Interface:

Valor em dólar

$ 100

↓

Cotação

5,40

↓

Valor convertido

R$ 540
19. Tabelas

Utilizar:

Bootstrap Table customizado.

Características:

paginação;
busca;
filtros;
ordenação;
exportação.

Exemplo:

Produtos:

Código

Produto

Custo

Venda

Lucro

Estoque

Ações
20. Estados das Tabelas

Sempre possuir:

Carregando

Skeleton Loading.

Sem dados

Mensagem amigável.

Exemplo:

Nenhum produto cadastrado.
Clique em Novo Produto.
Erro

Mensagem clara.

21. Modais

Utilizados para:

confirmações;
pequenos cadastros;
visualizações.

Evitar:

formulários gigantes dentro de modal.

22. Alertas

Utilizar:

SweetAlert2.

Tipos:

Sucesso:

Produto cadastrado.

Erro:

Não foi possível salvar.

Confirmação:

Deseja excluir este produto?
23. Feedback Visual

Toda ação deve informar resultado.

Exemplo:

Usuário salva produto:

Clique

↓

Loading

↓

Sucesso

Nunca deixar usuário esperando sem informação.

24. Responsividade

O sistema deverá funcionar em:

Desktop

Prioridade.

Tablet

Adaptado.

Smartphone

Operações essenciais.

Breakpoints:

Bootstrap padrão:

sm

md

lg

xl

xxl
25. Mobile

No celular:

Sidebar vira:

Menu Hamburger

Cards:

Uma coluna.

Tabelas:

Scroll horizontal.

26. Tela de Login

Características:

tela centralizada;
fundo moderno;
logo;
formulário compacto.

Modelo:

--------------------------------

       LOGO

   ImportControl


   Email

   Senha


   [ Entrar ]


 Esqueci minha senha

--------------------------------
27. Tela de Primeiro Acesso

Apresentar:

Bem-vindo ao ImportControl

Vamos configurar sua empresa.

1. Dados da empresa

2. Moeda principal

3. Primeiro produto

4. Primeiro usuário
28. Tela de Viagens

Layout:

Cards:

Viagem Atual

Miami

01/08/2026

Status:
Em andamento

Ações:

despesas;
produtos;
fechamento.
29. Tela de Produtos

Filtros:

Categoria

Fornecedor

Viagem

Estoque

Cards:

Imagem

Nome

Custo

Venda

Lucro

30. Tela de Venda

Fluxo:

Buscar cliente

↓

Adicionar produtos

↓

Alterar preço se necessário

↓

Aplicar desconto

↓

Selecionar pagamento

↓

Finalizar
31. Tela Financeira

Mostrar:

Entradas:

Vendas

Saídas:

Despesas

Viagens

Compras

Indicador:

Saldo Atual
32. Dashboard Financeiro

Informações:

faturamento;
custo;
lucro;
margem;
despesas.
33. Tema Claro

Tema inicial:

Light Mode.

Características:

fundo branco;
cards claros;
textos escuros.
34. Tema Escuro

Preparar arquitetura.

Futuro:

Dark Mode.

Não implementar inicialmente.

35. Ícones

Utilizar:

Bootstrap Icons

Padrão:

Dashboard:

bi-speedometer2

Produtos:

bi-box

Financeiro:

bi-cash-stack
36. Animações

Utilizar com moderação.

Permitido:

transição de menu;
hover;
loading.

Evitar:

animações excessivas.

37. Acessibilidade

Implementar:

contraste adequado;
labels;
navegação teclado;
textos alternativos.
38. Componentização Frontend

Organizar:

resources/js/components/

├── Modal.js

├── Table.js

├── CurrencyInput.js

├── DashboardCard.js

└── Alert.js
39. Javascript

Organização:

resources/js/

├── app.js

├── modules/

│
├── products.js

├── sales.js

├── finance.js

└── dashboard.js
40. AJAX

Utilizar para:

buscas;
filtros;
atualizações rápidas.

Exemplo:

Buscar produto:

Digite nome

↓

Consulta AJAX

↓

Resultado instantâneo
41. Loading

Sempre mostrar:

spinner;
skeleton;
bloqueio temporário.
42. Mensagens do Sistema

Tom:

Profissional.

Evitar:

Ops deu ruim

Usar:

Não foi possível concluir a operação.
Tente novamente.
43. Experiência SaaS

O usuário deverá sentir:

Tenho controle da minha operação.

Prioridades:

Rapidez.
Clareza.
Segurança.
Organização.
44. Checklist UX/UI

Antes de finalizar tela:

[ ] Responsiva

[ ] Segue identidade visual

[ ] Possui feedback

[ ] Possui validação

[ ] Possui estados vazios

[ ] Possui loading

[ ] Possui tratamento de erro
Encerramento da Parte 10

Esta definição estabelece o padrão visual completo do ImportControl.

Todas as telas futuras deverão seguir este Design System para manter:

consistência;
profissionalismo;
facilidade de uso;
aparência SaaS moderna.