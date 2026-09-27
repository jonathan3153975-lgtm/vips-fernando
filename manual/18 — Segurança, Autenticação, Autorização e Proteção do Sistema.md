# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 18 — Segurança, Autenticação, Autorização e Proteção do Sistema

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação de segurança, controle de acesso e proteção da aplicação

---

# 1. Objetivo

Esta seção define as regras de segurança do ImportControl.

O objetivo é garantir:

- proteção dos dados dos clientes;
- isolamento entre empresas SaaS;
- controle de acesso;
- rastreabilidade das ações;
- proteção contra ataques comuns;
- conformidade com boas práticas de segurança.

---

# 2. Princípios de Segurança

O sistema deverá seguir os princípios:


Menor privilégio

Defesa em profundidade

Separação de responsabilidades

Auditoria completa

Proteção por padrão


---

# 3. Modelo de Segurança SaaS

O ImportControl será um sistema Multi-Tenant.

Cada empresa possuirá:

- usuários próprios;
- produtos próprios;
- clientes próprios;
- vendas próprias;
- dados financeiros próprios.

---

Regra fundamental:


Usuário da empresa A

NUNCA

poderá acessar dados da empresa B


---

# 4. Identificação do Tenant

Todo usuário estará vinculado a:


tenant_id


---

Fluxo:


Login

↓

Identificar usuário

↓

Carregar tenant

↓

Aplicar filtro automático

↓

Liberar acesso


---

# 5. Autenticação

O sistema utilizará:


Login + Senha


---

Campos:


Email

Senha

Status

Tenant


---

Exemplo:


usuario@email.com


---

# 6. Cadastro de Usuário

Campos:


Nome

Email

Telefone

Senha

Perfil

Status

Foto


---

Validações:

- email único dentro do tenant;
- senha mínima;
- usuário ativo.

---

# 7. Armazenamento de Senhas

Nunca armazenar senha em texto puro.

Utilizar:

```php
password_hash()

Exemplo:

Entrada:

Senha123

Banco:

$2y$10$8d9fj...
8. Verificação de Senha

Utilizar:

password_verify()

Nunca:

MD5()

SHA1()
9. Controle de Sessão

Após login:

Criar sessão:

user_id

tenant_id

role_id

permissions

Exemplo:

$_SESSION['user_id']

$_SESSION['tenant_id']
10. Expiração de Sessão

Configurar:

Tempo máximo de inatividade

Exemplo:

30 minutos

Após expiração:

Usuário deverá autenticar novamente.

11. Proteção Contra Fixação de Sessão

Após login:

Executar:

session_regenerate_id(true);

Objetivo:

Evitar roubo de sessão.

12. Logout

Ao sair:

Executar:

destruir sessão;
limpar cookies;
remover dados temporários.
13. Recuperação de Senha

Fluxo:

Usuário informa email

↓

Sistema gera token

↓

Envia link

↓

Usuário redefine senha
14. Tokens de Recuperação

Tabela:

password_resets

Campos:

id

user_id

token

expires_at

used_at

Regras:

uso único;
expiração;
token aleatório.
15. Autorização por Perfil

O sistema terá inicialmente:

Desenvolvedor

Controle total do SaaS.

Permissões:

Gerenciar empresas

Gerenciar planos

Gerenciar usuários

Acessar logs
Usuário Empresa

Executa operações do negócio.

Permissões:

Produtos

Vendas

Financeiro

Clientes

Relatórios
16. Sistema RBAC

(Role Based Access Control)

Modelo:

Usuário

↓

Perfil

↓

Permissões

↓

Ações permitidas

Exemplo:

João

↓

Vendedor

↓

sales.create

↓

Pode criar vendas
17. Middleware de Autenticação

Criar:

AuthMiddleware

Responsabilidade:

Verificar:

usuário logado;
sessão válida;
tenant ativo.

Fluxo:

Request

↓

AuthMiddleware

↓

Controller
18. Middleware de Permissão

Criar:

PermissionMiddleware

Exemplo:

Rota:

DELETE /products/10

Permissão necessária:

products.delete

Sem permissão:

Retornar:

403 Forbidden
19. Proteção Multi-Tenant

Toda consulta deverá obrigatoriamente filtrar:

tenant_id

Errado:

SELECT *

FROM products

Correto:

SELECT *

FROM products

WHERE tenant_id = ?
20. Proteção SQL Injection

Utilizar:

PDO Prepared Statements.

Nunca:

$sql =
"SELECT * FROM users WHERE id=".$id;

Correto:

$stmt =
$pdo->prepare(
"SELECT * FROM users WHERE id=?"
);
21. Proteção XSS

Todo conteúdo exibido deverá ser tratado.

Utilizar:

htmlspecialchars()

Exemplo:

Entrada:

<script>alert()</script>

Resultado:

Texto simples.

22. Proteção CSRF

Todos os formulários deverão possuir token.

Fluxo:

Abrir formulário

↓

Gerar token

↓

Enviar

↓

Validar servidor

Exemplo:

<input 
type="hidden"
name="csrf"
value="TOKEN"
/>
23. Upload Seguro de Arquivos

Arquivos permitidos:

PDF

JPG

PNG

WEBP

Validar:

extensão;
MIME;
tamanho;
nome.

Nunca confiar no nome enviado.

24. Armazenamento de Arquivos

Não salvar diretamente:

public/uploads

Utilizar:

storage/uploads

Exemplo:

storage/uploads/

tenant_1/

products/

arquivo.jpg
25. Limite de Upload

Configurar:

Exemplo:

Imagem:

5MB


Documento:

10MB
26. Proteção contra Brute Force

Após tentativas:

5 erros consecutivos

Ação:

Bloquear temporariamente

Tabela:

login_attempts
27. Histórico de Login

Registrar:

Usuário

Data

IP

Dispositivo

Resultado

Exemplo:

01/08/2026

Login sucesso

IP 200.xxx.xxx
28. Auditoria do Sistema

Toda ação importante deverá gerar log.

Eventos:

Login

Criar produto

Excluir produto

Alterar preço

Cancelar venda

Alterar financeiro
29. Tabela audit_logs

Estrutura:

CREATE TABLE audit_logs (

id BIGINT PRIMARY KEY,

tenant_id BIGINT,

user_id BIGINT,

module VARCHAR(50),

action VARCHAR(50),

old_data JSON,

new_data JSON,

ip VARCHAR(50),

created_at DATETIME

);
30. Registro de Alteração de Valores

Especial atenção:

Preço

Registrar:

Antes

Depois

Usuário

Data
Financeiro

Registrar:

Valor anterior

Novo valor

Motivo
31. Segurança Financeira

Operações financeiras deverão exigir:

permissão específica;
confirmação;
auditoria.

Exemplo:

Excluir lançamento:

Digite motivo:

_____________

Confirmar
32. LGPD

O sistema deverá respeitar:

Lei Geral de Proteção de Dados.

Dados pessoais:

Nome

CPF

Telefone

Email

Endereço
33. Princípios LGPD

Aplicar:

Finalidade

Necessidade

Segurança

Transparência
34. Exportação de Dados

Preparar recurso:

Usuário poderá solicitar:

Exportação dos seus dados

Formato:

JSON

CSV
35. Exclusão de Dados

Não apagar dados críticos diretamente.

Aplicar:

Soft Delete

Exemplo:

deleted_at
36. Backup e Recuperação

Estratégia:

Diário

Backup automático.

Semanal

Backup externo.

Mensal

Teste de restauração.

37. Monitoramento

Registrar:

erros;
falhas;
tentativas suspeitas.

Preparar integração futura:

Sentry

Logs externos

Monitoramento servidor
38. Segurança de API

Todas APIs deverão exigir:

Authentication Token

ou

Session válida

Nunca disponibilizar:

dados sem autenticação;
endpoints administrativos públicos.
39. Cabeçalhos de Segurança

Configurar:

X-Frame-Options

X-Content-Type-Options

Content-Security-Policy

Strict-Transport-Security
40. HTTPS Obrigatório

Em produção:

HTTPS obrigatório

Nunca transmitir:

senha;
tokens;
dados pessoais

sem criptografia.

41. Logs de Erro

Nunca exibir:

SQL completo

Senha

Token

Dados sensíveis

Usuário recebe:

Ocorreu um erro inesperado.

Administrador recebe detalhes no log.

42. Ambiente de Desenvolvimento

Separar:

Development

Testing

Production

Exemplo:

Desenvolvimento:

DEBUG=true

Produção:

DEBUG=false
43. Checklist de Segurança
[ ] Senhas criptografadas

[ ] Sessão segura

[ ] Controle RBAC

[ ] Multi-tenant protegido

[ ] SQL Injection protegido

[ ] XSS protegido

[ ] CSRF protegido

[ ] Upload seguro

[ ] Auditoria

[ ] Logs

[ ] HTTPS

[ ] Backup