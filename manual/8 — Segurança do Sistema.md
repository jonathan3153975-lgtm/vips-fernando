# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 8 — Segurança do Sistema

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Arquitetura de Segurança, Autenticação, Autorização e Proteção de Dados

---

# 1. Objetivo

Esta seção define todos os requisitos de segurança do ImportControl.

Como o sistema será desenvolvido como uma plataforma SaaS Multi-Tenant, a segurança é um dos pilares fundamentais do projeto.

O sistema deverá garantir:

- confidencialidade dos dados;
- integridade das informações;
- disponibilidade;
- isolamento entre empresas;
- rastreabilidade das operações;
- proteção contra ataques;
- conformidade com boas práticas de segurança.

---

# 2. Princípios de Segurança

O desenvolvimento deverá seguir:

- Security by Design;
- Least Privilege Principle;
- Defense in Depth;
- Fail Secure;
- Zero Trust;
- Privacy by Default.

---

# 3. Modelo de Segurança

A segurança será dividida em camadas:
Usuário

↓

Autenticação

↓

Sessão / Token

↓

Middleware

↓

Tenant Validation

↓

Permission Check

↓

Controller

↓

Service

↓

Repository

↓

Database


---

# 4. Autenticação

A autenticação será baseada em:

- email;
- senha;
- sessão segura.

---

Fluxo:


Usuário informa email e senha

↓

Sistema busca usuário

↓

Verifica status

↓

Valida senha

↓

Carrega tenant

↓

Carrega permissões

↓

Cria sessão

↓

Acesso liberado


---

# 5. Cadastro de Usuário

Todo usuário deverá possuir:

- nome;
- email;
- senha;
- tenant;
- perfil;
- status.

---

Campos obrigatórios:


name

email

password

role_id

status


---

# 6. Política de Senhas

Requisitos mínimos:

- mínimo 8 caracteres;
- pelo menos uma letra maiúscula;
- pelo menos uma letra minúscula;
- pelo menos um número;
- recomenda-se caractere especial.

---

Exemplo:

Aceito:


Import@2026


Não aceitar:


12345678


---

# 7. Armazenamento de Senhas

Nunca armazenar senha em texto puro.

Obrigatório:

```php
password_hash()

Algoritmo recomendado:

PASSWORD_DEFAULT

ou:

PASSWORD_ARGON2ID

quando disponível.

8. Recuperação de Senha

Fluxo:

Usuário solicita recuperação

↓

Sistema gera token temporário

↓

Envia email

↓

Usuário acessa link

↓

Define nova senha

↓

Token invalidado

Tabela:

password_resets

Campos:

id

email

token

expires_at

created_at
9. Expiração de Sessão

Sessões deverão possuir:

tempo máximo de duração;
renovação controlada;
encerramento seguro.

Configuração sugerida:

Sessão inativa:

30 minutos

Sessão máxima:

8 horas

10. Proteção Contra Session Hijacking

Implementar:

regeneração do ID da sessão após login;
cookies seguros;
HttpOnly;
SameSite;
HTTPS obrigatório.

Cookie:

Secure = true

HttpOnly = true

SameSite = Strict
11. Logout Seguro

Ao sair:

Sistema deverá:

destruir sessão;
invalidar tokens;
limpar cookies;
registrar evento.
12. Controle de Acesso (RBAC)

O sistema utilizará:

Role Based Access Control.

Modelo:

Usuário

↓

Role

↓

Permission

↓

Action
13. Permissões

Formato:

modulo.acao

Exemplos:

Produtos:

products.view

products.create

products.edit

products.delete

Vendas:

sales.create

sales.cancel

sales.view

Usuários:

users.create

users.delete
14. Middleware de Permissão

Toda rota deverá validar:

usuário autenticado;
tenant;
permissão.

Exemplo:

/products/delete

Requer:

products.delete
15. Usuário Developer

Usuário especial da plataforma.

Características:

acesso global;
não pertence a tenant operacional;
administra clientes SaaS.

Permissões:

tenants.manage

plans.manage

system.logs

system.metrics
16. Isolamento Multi-Tenant

Regra crítica:

Nenhum dado poderá ser acessado sem validação de tenant.

Exemplo proibido:

SELECT *
FROM sales
WHERE id = 100;

Exemplo correto:

SELECT *
FROM sales
WHERE id = 100

AND tenant_id = 10;
17. Tenant Guard

Toda operação deverá passar por:

TenantGuard

Responsável por:

validar contexto;
impedir troca de tenant;
bloquear acesso indevido.
18. Proteção Contra IDOR

IDOR:

Insecure Direct Object Reference.

Exemplo de ataque:

Usuário tenta:

/sale/100

Alterar para:

/sale/101

Tentando acessar venda de outro cliente.

Proteção:

Sempre validar:

registro pertence ao tenant atual
19. CSRF Protection

Todas as requisições POST deverão possuir token CSRF.

Protege contra:

Cross Site Request Forgery.

Exemplo:

Formulário:

<input 
type="hidden"
name="_token"
value="TOKEN"
/>
20. XSS Protection

Toda saída HTML deverá ser escapada.

Nunca:

echo $userInput;

Correto:

htmlspecialchars($userInput);
21. SQL Injection

Proteção:

Obrigatório utilizar:

PDO;
prepared statements;
parâmetros.

Nunca:

$sql =
"SELECT *
FROM users
WHERE email='$email'";

Correto:

$sql =
"SELECT *
FROM users
WHERE email=?";
22. Upload Seguro

Arquivos enviados pelo usuário deverão ser validados.

Validar:

extensão;
MIME;
tamanho;
nome;
conteúdo.

Extensões permitidas inicialmente:

jpg

jpeg

png

pdf
23. Armazenamento de Arquivos

Nunca salvar uploads em:

public/

Diretamente.

Utilizar:

storage/uploads/

Estrutura:

storage/

uploads/

tenants/

001/

products/

24. Criptografia

Dados sensíveis deverão ser protegidos.

Exemplos:

tokens;
chaves externas;
dados privados.

Utilizar:

AES-256 quando necessário.

25. Variáveis Sensíveis

Nunca armazenar no código:

senha banco;
tokens;
chaves API.

Utilizar:

.env

Exemplo:

DB_PASSWORD=

MAIL_PASSWORD=

API_SECRET=
26. HTTPS

Obrigatório em produção.

Todo acesso deverá utilizar:

HTTPS

Redirecionar:

HTTP

↓

HTTPS
27. Headers de Segurança

Implementar:

X-Frame-Options

X-Content-Type-Options

Content-Security-Policy

Referrer-Policy
28. Rate Limiting

Proteção contra abuso.

Aplicar em:

Login

API

Recuperação de senha

Uploads

Exemplo:

Login:

5 tentativas / 15 minutos
29. Bloqueio Temporário

Após várias tentativas:

Usuário poderá ser bloqueado temporariamente.

Exemplo:

5 erros

↓

Bloqueio 15 minutos
30. Logs de Segurança

Registrar:

Login realizado

Login falho

Alteração de senha

Mudança de permissão

Acesso negado

Alteração crítica

Tabela:

security_logs

Campos:

id

tenant_id

user_id

event

ip

browser

created_at
31. Auditoria

Toda ação importante deverá ser registrada.

Exemplos:

Usuário alterou preço:

Antes:
R$500

Depois:
R$450

Usuário excluiu produto:

Registrar:

quem;
quando;
qual produto;
motivo.
32. Proteção de Dados Financeiros

Operações financeiras deverão:

utilizar transações;
possuir histórico;
nunca permitir alteração silenciosa.
33. Exclusão de Dados

Nunca excluir dados críticos.

Utilizar:

Soft Delete.

Exemplo:

Produto removido:

deleted_at = 2026-08-01
34. Backup e Recuperação

O sistema deverá possuir:

Backup diário.

Tipos:

completo;
incremental.

Restaurar:

tenant específico;
banco completo.
35. LGPD

O sistema deverá atender:

Lei Geral de Proteção de Dados.

Direitos:

acesso;
correção;
exclusão;
portabilidade;
informação.
36. Dados Pessoais Armazenados

Exemplos:

Clientes:

nome;
telefone;
email;
documento.

Usuários:

nome;
email.
37. Consentimento

Quando aplicável:

Registrar:

aceite;
data;
versão do termo.

Tabela:

user_consents
38. Ambiente de Desenvolvimento

Nunca utilizar:

dados reais;
senhas reais;
tokens reais.

Utilizar:

dados fictícios.

39. Testes de Segurança

Realizar testes:

autenticação;
autorização;
SQL injection;
XSS;
CSRF;
upload;
isolamento tenant.
40. Checklist de Segurança

Antes de publicar:

[ ] HTTPS ativo

[ ] Senhas criptografadas

[ ] CSRF protegido

[ ] SQL preparado

[ ] Tenant isolado

[ ] Logs ativos

[ ] Backup configurado

[ ] Permissões revisadas

[ ] Upload seguro

[ ] Cookies seguros
41. Segurança para Uso com IA (GitHub Copilot)

Ao gerar código, a IA deverá:

Sempre:

validar entrada;
respeitar tenant_id;
usar Repository;
usar Prepared Statements;
aplicar permissões.

Nunca:

criar SQL direto em Controller;
expor dados sensíveis;
ignorar autenticação;
retornar informações de outro tenant.
Encerramento da Parte 8

A segurança do ImportControl deverá ser tratada como requisito estrutural e não como complemento.

A arquitetura definida permite que o sistema cresça como SaaS mantendo:

isolamento entre clientes;
proteção dos dados;
rastreabilidade;
conformidade;
confiabilidade.