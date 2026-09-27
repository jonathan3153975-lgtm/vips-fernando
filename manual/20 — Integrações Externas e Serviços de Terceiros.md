# PRODUCT REQUIREMENTS DOCUMENT (PRD)

# PARTE 20 — Integrações Externas e Serviços de Terceiros

**Projeto:** ImportControl  
**Versão:** 1.0  
**Documento:** Especificação das integrações externas, APIs e serviços complementares

---

# 1. Objetivo

Esta seção define as integrações externas previstas para o ImportControl.

O objetivo é permitir que o sistema se conecte com serviços especializados, reduzindo processos manuais e aumentando a automação.

As integrações deverão seguir os princípios:

- baixo acoplamento;
- segurança;
- facilidade de substituição;
- controle de erros;
- registro de logs;
- possibilidade de ativação/desativação por configuração.

---

# 2. Arquitetura de Integrações

Todas integrações deverão utilizar uma camada intermediária.

Estrutura:


Sistema

↓

Service de Integração

↓

API Externa

↓

Resposta

↓

Tratamento

↓

Banco de Dados


---

# 3. Organização de Código

Criar:


app/

└── Integrations/

├── MercadoPago/

├── ExchangeRate/

├── Email/

├── Storage/

├── Notifications/

└── Pdf/

---

# 4. Padrão de Integração

Cada integração deverá possuir:


Client

Service

Config

Exception

Logger


---

Exemplo:


MercadoPagoClient

↓

MercadoPagoService

↓

PaymentController


---

# 5. Variáveis de Ambiente

Todas as credenciais deverão ficar no:


.env


---

Exemplo:

```env
MERCADOPAGO_ACCESS_TOKEN=

MERCADOPAGO_PUBLIC_KEY=

SMTP_HOST=

SMTP_USER=

SMTP_PASSWORD=

EXCHANGE_API_KEY=

Nunca armazenar:

tokens;
senhas;
chaves privadas

no código fonte.

6. Integração Mercado Pago
Objetivo

Permitir cobrança das assinaturas SaaS.

Uso:

planos;
pagamentos recorrentes;
confirmação automática;
controle financeiro.
7. Fluxo Mercado Pago
Empresa escolhe plano

↓

Sistema cria preferência

↓

Usuário realiza pagamento

↓

Mercado Pago processa

↓

Webhook retorna status

↓

Sistema atualiza assinatura
8. Funcionalidades Mercado Pago

Implementar:

Criar pagamento
POST /payment/create
Consultar pagamento
GET /payment/{id}
Receber webhook
POST /webhook/mercadopago
9. Webhook Mercado Pago

Responsável por receber:

pagamento aprovado;
pagamento recusado;
pagamento pendente;
cancelamento.

Exemplo:

Pagamento:

ID 123456


Status:

approved

Atualização:

subscription.status

=

ACTIVE
10. Segurança Webhook

Validar:

assinatura;
origem;
token;
payload.

Nunca confiar apenas no retorno recebido.

11. Histórico de Pagamentos

Criar tabela:

payment_transactions

Campos:

id

tenant_id

gateway

transaction_id

amount

status

payload

created_at
12. API de Cotação de Moedas
Objetivo

Facilitar conversão:

USD → BRL

EUR → BRL

Uso:

cadastro de viagens;
compras internacionais;
cálculo de custo.
13. Serviço de Câmbio

Criar:

ExchangeRateService

Responsável por:

consultar cotação;
salvar histórico;
atualizar valores.
14. Fluxo de Cotação
Usuário informa moeda

↓

Sistema consulta API

↓

Recebe cotação

↓

Usuário confirma

↓

Salva histórico
15. Histórico Cambial

Nunca sobrescrever valores antigos.

Exemplo:

Compra realizada:

USD

Cotação:

5,10

Mesmo que depois:

USD

Cotação:

5,80

A compra continuará usando:

5,10
16. APIs Compatíveis

O sistema deverá permitir troca de fornecedor.

Exemplo:

ExchangeRateAPI

AwesomeAPI

Banco Central

Serviço próprio

Criar interface:

interface ExchangeRateProvider
{

public function getRate(
string $currency
);

}
17. Integração de Email
Objetivo

Enviar comunicações automáticas.

Utilizações:

recuperação de senha;
confirmação cadastro;
notificações;
cobranças;
relatórios.
18. Serviço de Email

Criar:

EmailService

Métodos:

send()

sendTemplate()

sendAttachment()
19. Templates de Email

Criar:

resources/

emails/

├── welcome.html

├── password-reset.html

├── payment.html

└── notification.html
20. SMTP

Suportar:

Gmail SMTP

Amazon SES

SendGrid

Servidor próprio
21. Integração WhatsApp (Futuro)

Objetivo:

Enviar:

avisos;
cobranças;
notificações.

Possíveis provedores:

WhatsApp Business API

Z-API

Twilio
22. Armazenamento de Arquivos

Arquivos:

fotos produtos;
notas fiscais;
documentos;
comprovantes.

Inicial:

Storage local

Futuro:

Amazon S3

Cloudflare R2

Google Cloud Storage
23. Abstração de Storage

Criar:

interface StorageProvider
{

upload();

delete();

getUrl();

}

Permite trocar:

Servidor próprio

↓

AWS S3

sem alterar o sistema.

24. Geração de PDF
Objetivo

Gerar:

relatórios;
comprovantes;
pedidos;
documentos.

Biblioteca recomendada:

mPDF
25. Relatórios PDF

Exemplos:

Relatório de venda

Contém:

Cliente

Produtos

Valores

Pagamento

Lucro
Relatório de viagem

Contém:

Despesas

Compras

Conversões

Resultado
26. Exportação Excel/CSV

Permitir:

Exportar:

produtos;
vendas;
clientes;
financeiro.

Formatos:

CSV

XLSX
27. Biblioteca Excel

Utilizar:

PhpSpreadsheet

Exemplo:

Produtos.xlsx
28. Notificações Internas

Criar:

NotificationService

Tipos:

INFO

WARNING

SUCCESS

ERROR

Exemplos:

Produto cadastrado

Estoque baixo

Pagamento aprovado
29. Push Notifications (Futuro)

Preparar integração:

Firebase Cloud Messaging

Uso:

Aplicativo mobile.

30. Integração com Leitor de Código de Barras

Futuro:

Permitir:

cadastro rápido;
venda rápida;
controle estoque.

Compatível:

USB Scanner

Bluetooth Scanner

Câmera celular
31. Integração com Impressoras

Futuro:

Impressão:

etiquetas;
comprovantes;
relatórios.
32. Integração Fiscal (Futuro)

Possível evolução:

emissão NF-e;
NFC-e;
integração contábil.

Arquitetura deverá permitir:

FiscalService
33. Integração com Inteligência Artificial (Futuro)

Possibilidades:

Análise de vendas

Exemplo:

Qual produto vende mais?
Sugestão de preço

Exemplo:

Preço ideal baseado em margem
Previsão estoque

Exemplo:

Comprar novamente em 20 dias
34. Controle de Erros

Toda integração deverá tratar:

timeout;
indisponibilidade;
resposta inválida;
limite de API.

Exemplo:

try {

$service->send();

}

catch(Exception $e){

Logger::error($e);

}
35. Logs de Integração

Criar:

integration_logs

Campos:

id

service

request

response

status

created_at
36. Cache de Integrações

Aplicar quando necessário.

Exemplo:

Cotação:

Buscar uma vez por dia

Evitar:

chamadas excessivas;
custos desnecessários.
37. Configuração por Tenant

Algumas integrações poderão ser individuais.

Exemplo:

Empresa usa:

Seu próprio SMTP

Estrutura:

tenant_integrations
38. Ativação de Integrações

Criar configuração:

Sistema

↓

Integrações

↓

Ativar/desativar

Exemplo:

[✓] Mercado Pago

[✓] Email

[ ] WhatsApp
39. Segurança das Integrações

Aplicar:

criptografia de credenciais;
mascaramento de logs;
rotação de tokens;
permissões restritas.
40. Testes de Integração

Criar:

tests/

Integration/

Testar:

APIs;
respostas;
falhas;
autenticação.
41. Critérios de Aceitação
[ ] Mercado Pago integrado

[ ] Webhook funcionando

[ ] Cotação automática

[ ] Email funcionando

[ ] PDF gerado

[ ] Exportações funcionando

[ ] Storage preparado

[ ] Logs criados

[ ] Tratamento de erros
Encerramento da Parte 20

A arquitetura de integrações prepara o ImportControl para evoluir de um sistema administrativo para uma plataforma SaaS completa.

O sistema estará preparado para integrar:

pagamentos;
câmbio;
comunicação;
armazenamento;
relatórios;
inteligência futura.