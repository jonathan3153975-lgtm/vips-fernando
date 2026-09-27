# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 17 — Arquitetura Frontend, Bootstrap Customizado, UX/UI e Design System

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação visual, experiência do usuário e arquitetura frontend

---

# 1. Objetivo

Esta seção define a arquitetura frontend do ImportControl.

O objetivo é criar uma interface:

- moderna;
- limpa;
- profissional;
- responsiva;
- intuitiva;
- agradável para uso diário;
- adequada para um sistema SaaS comercial.

---

# 2. Conceito Visual

O sistema deverá transmitir:


Confiança

Tecnologia

Organização

Controle

Profissionalismo


---

A identidade visual será baseada nas cores:


Azul

Vermelho

Branco

Cinza neutro


---

# 3. Identidade de Cores

## Cor Primária

Azul.

Uso:

- menu;
- botões principais;
- cabeçalhos;
- indicadores.

Representa:


Confiança

Segurança

Tecnologia


---

## Cor Secundária

Vermelho.

Uso:

- alertas;
- ações importantes;
- valores negativos;
- exclusões.

Representa:


Atenção

Importância

Ação


---

## Branco

Uso:

- fundos;
- cards;
- áreas de conteúdo.

---

## Cinza

Uso:

- textos secundários;
- divisores;
- tabelas.

---

# 4. Design System

Criar biblioteca interna de componentes.

Estrutura:


assets/

├── css/

│

├── variables.css

├── components.css

├── dashboard.css

├── js/

├── app.js

├── ajax.js

├── charts.js

└── components/


---

# 5. Framework Frontend

Utilizar:


Bootstrap 5+


---

Customização:

Não utilizar Bootstrap puro.

Criar:


Tema próprio ImportControl


---

# 6. Variáveis CSS

Arquivo:


variables.css


---

Exemplo:

```css
:root {

--primary-color:#0d6efd;

--secondary-color:#dc3545;

--background:#f8f9fa;

--card-radius:14px;

--shadow:
0 5px 20px rgba(0,0,0,.08);

}
7. Layout Principal

Estrutura:

------------------------------------------------

Header

------------------------------------------------


Sidebar

|

|

Content Area


------------------------------------------------

Footer

------------------------------------------------
8. Dashboard Layout

Tela inicial:

------------------------------------------------

Olá, João

Resumo do negócio


------------------------------------------------


[ Faturamento ]

[ Lucro ]

[ Estoque ]

[ Clientes ]


------------------------------------------------


Gráfico de vendas


------------------------------------------------


Alertas


------------------------------------------------
9. Sidebar

Menu lateral:

Dashboard

Viagens

Produtos

Estoque

Vendas

Clientes

Financeiro

Relatórios

Configurações

Características:

recolhível;
responsivo;
ícones;
destaque da página atual.
10. Header

Informações:

Logo

Nome empresa

Busca

Notificações

Usuário

Exemplo:

ImportControl

[Pesquisar]

🔔

João ▼
11. Cards do Sistema

Padrão:

Card

├── Ícone

├── Título

├── Valor

└── Indicador

Exemplo:

--------------------------------

💰

Faturamento

R$35.000

↑ 15%

--------------------------------
12. Botões

Criar padrões:

Primário

Ações principais.

Exemplo:

Novo Produto
Secundário

Ações auxiliares.

Perigo

Ações destrutivas.

Exemplo:

Excluir
13. Tabelas

Todas as tabelas deverão possuir:

busca;
ordenação;
paginação;
filtros.

Exemplo:

Produto

Categoria

Custo

Venda

Estoque

Ações
14. DataTables

Utilizar:

jQuery DataTables

Recursos:

paginação;
pesquisa;
exportação;
ordenação.
15. Formulários

Padrão:

Label

Input

Mensagem validação

Exemplo:

Nome Produto

[________________]


✓ Nome válido
16. Validação Frontend

Utilizar:

JavaScript.

Validar:

campos obrigatórios;
formatos;
valores;
datas.

Mas sempre validar novamente no backend.

17. Modal

Utilizar:

Bootstrap Modal.

Aplicações:

cadastro rápido;
confirmação;
detalhes.

Exemplo:

Deseja excluir este produto?

[Cancelar]

[Excluir]
18. Alertas

Utilizar:

SweetAlert2

Exemplo:

Sucesso:

Produto cadastrado com sucesso!

Erro:

Não foi possível concluir.
19. Notificações

Sistema deverá possuir notificações internas.

Exemplos:

Estoque baixo

Conta vencendo

Pagamento recebido

Venda realizada
20. Responsividade

O sistema deverá funcionar em:

Desktop

Principal ambiente.

Tablet

Uso em viagens.

Smartphone

Consulta rápida.

Breakpoints:

Mobile

Tablet

Desktop
21. Dashboard Mobile

Adaptar:

Desktop:

4 cards lado a lado

Mobile:

Card

Card

Card

Card
22. Componentes Reutilizáveis

Criar:

components/

├── Card.php

├── Button.php

├── Table.php

├── Modal.php

├── Alert.php

└── Pagination.php
23. Templates

Estrutura:

views/

├── layouts/

│

├── header.php

├── sidebar.php

├── footer.php


├── dashboard/


├── products/


├── sales/


└── finance/
24. JavaScript

Organização:

assets/js/

app.js

ajax.js

products.js

sales.js

finance.js

dashboard.js
25. Padrão JavaScript

Evitar:

Código espalhado.

Preferir:

Módulos.

Exemplo:

const Product = {

save(){

}

load(){

}

}
26. Comunicação AJAX

Utilizar:

Fetch API

ou

jQuery AJAX

Fluxo:

Usuário

↓

JavaScript

↓

Controller API

↓

JSON

↓

Atualização tela
27. APIs Frontend

Exemplos:

Buscar produtos:

GET

/api/products

Salvar:

POST

/api/products
28. Máscaras

Utilizar:

Inputmask

Aplicações:

CPF:

000.000.000-00

Moeda:

R$ 1.500,00

Telefone:

(00)00000-0000
29. Gráficos

Utilizar:

Chart.js

Tipos:

Linha

Fluxo financeiro.

Barra

Produtos vendidos.

Pizza

Categorias.

30. Dashboard Financeiro

Componentes:

Saldo

Receitas

Despesas

Lucro

Contas vencendo
31. Dashboard Produtos

Mostrar:

Produtos cadastrados

Valor estoque

Produtos sem estoque

Mais vendidos
32. Dashboard Vendas

Mostrar:

Vendas hoje

Venda mês

Ticket médio

Lucro médio
33. Dashboard Viagens

Mostrar:

Viagens abertas

Investimento total

Produtos importados

Custos
34. Tema Escuro (Futuro)

Preparar arquitetura.

Possibilidade:

Light Mode

Dark Mode
35. Acessibilidade

Aplicar:

contraste adequado;
labels;
navegação teclado;
tamanhos adequados.
36. Performance

Aplicar:

carregamento sob demanda;
compressão CSS;
minificação JS;
imagens otimizadas.
37. Segurança Frontend

Implementar:

proteção CSRF;
escape HTML;
validação;
controle de sessão.
38. UX do Processo de Venda

Venda deverá exigir poucos passos:

1 - Buscar cliente

2 - Adicionar produto

3 - Definir preço

4 - Pagamento

5 - Finalizar
39. UX do Cadastro de Produto

Fluxo:

Produto

↓

Categoria

↓

Custo

↓

Preço

↓

Estoque
40. UX Financeiro

Objetivo:

Mostrar informações sem exigir conhecimento contábil.

Exemplo:

Ao invés de:

DRE

Mostrar:

Quanto ganhei este mês?
41. Critérios de Aceitação
[ ] Layout moderno

[ ] Bootstrap customizado

[ ] Responsividade

[ ] Componentes reutilizáveis

[ ] Dashboard funcional

[ ] AJAX integrado

[ ] Gráficos

[ ] Validações frontend

[ ] Tema preparado

[ ] Boa experiência mobile
Encerramento da Parte 17

A arquitetura frontend proposta transforma o ImportControl em um sistema SaaS profissional, com aparência moderna e experiência semelhante a plataformas comerciais atuais.

O foco será:

simplicidade;
velocidade;
clareza;
facilidade de uso.
