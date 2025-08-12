# HandoffFlow: Transbordo Inteligente de Atendimento

![HandoffFlow Banner](https://github.com/Sertoriel)

**A ponte perfeita entre automação e atendimento humano no WhatsApp.**  
HandoffFlow é um sistema avançado de transbordo que conecta chatbots a atendentes humanos e vice-versa, criando uma experiência contínua e sem atritos para seus clientes.

[![Laravel Version](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel)](https://laravel.com)
[![Twilio Integration](https://img.shields.io/badge/Twilio-API-FF22B8?logo=twilio)](https://twilio.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

## ✨ Funcionalidades Principais

- **Transbordo automático** de chatbot para atendente humano
- **Retorno inteligente** ao chatbot após atendimento
- Painel de controle para operadores em tempo real
- Histórico completo de conversas
- Configuração simplificada via Twilio Studio
- Sistema de notificação para novos atendimentos
- Suporte multioperador (opcional)
- Redirecionamento contextual pós-atendimento

## 🧩 Tecnologias Utilizadas

- **Backend**: Laravel 11.x
- **Frontend**: Blade Templates, Livewire (opcional)
- **API**: Twilio WhatsApp API
- **Banco de Dados**: MySQL/PostgreSQL/SQLite
- **Fila**: Redis (recomendado) ou Database
- **Autenticação**: Laravel Sanctum

## 🚀 Pré-requisitos

Antes de começar, verifique se você tem instalado:

- PHP 8.2+
- Composer 2.x
- Node.js 18.x (para assets)
- Banco de dados (MySQL, PostgreSQL ou SQLite)
- Conta no [Twilio](https://twilio.com)
- Número de WhatsApp configurado no Sandbox Twilio

## 📥 Instalação Passo a Passo

### 1. Clonar o Repositório

```bash
git clone https://github.com/seu-usuario/handoffflow.git
cd handoffflow
```

### 2. Instalar Dependências

```bash
composer install
npm install
npm run build
```

### 3. Configurar Ambiente

Copie o arquivo de exemplo `.env.example` para `.env` e configure as variáveis:

```bash
cp .env.example .env
php artisan key:generate
```

Edite o arquivo `.env` com suas configurações:

```ini
APP_NAME=HandoffFlow
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=handoffflow
DB_USERNAME=root
DB_PASSWORD=

TWILIO_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_WHATSAPP_NUMBER=whatsapp:+14155238886

QUEUE_CONNECTION=database # ou redis para melhor performance
```

### 4. Configurar Banco de Dados

Crie o banco de dados e execute as migrações:

```bash
php artisan migrate
php artisan db:seed # para dados de teste opcionais
```

### 5. Configurar Twilio

1. Acesse o [Console Twilio](https://console.twilio.com/)
2. Vá para WhatsApp > Sandbox Settings
3. Configure:
   - **WHEN A MESSAGE COMES IN**: `POST` → `{APP_URL}/api/twilio/webhook`
   - **STATUS CALLBACK URL**: (Opcional)

![Twilio Config](https://via.placeholder.com/600x200?text=Twilio+Sandbox+Configuration)

### 6. Configurar Studio Flow

1. Crie um novo Flow no Twilio Studio
2. Adicione um widget **TwilioML Redirect** com:
   ```json
   {
     "method": "POST",
     "url": "{APP_URL}/api/twilio/handoff"
   }
   ```
3. Publique o Flow e anote o SID

## � Estrutura de Diretórios

```
handoffflow/
├── app/
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── TwilioWebhookController.php
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   │   └── ChatSession.php
│   └── Providers/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── js/
│   ├── lang/
│   └── views/
│       └── operator.blade.php
├── routes/
│   ├── api.php
│   └── web.php
├── storage/
├── tests/
└── vendor/
```

## 🚦 Executando o Sistema

### Ambiente de Desenvolvimento

```bash
php artisan serve
php artisan queue:work # para processar jobs em segundo plano
```

Acesse o painel do operador:
```
http://localhost:8000/operator
```

### Ambiente de Produção

Configure seu servidor web (Nginx/Apache) para apontar para o diretório `public/` e execute:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:work --daemon # para processamento contínuo de filas
```

## 🧪 Testando o Fluxo

1. Envie "join <sandbox-code>" para o número do Sandbox Twilio
2. Envie "atendente" para iniciar o transbordo
3. Acesse `http://localhost:8000/operator`
4. Selecione a sessão ativa e interaja
5. Clique em "Finalizar Atendimento" para retornar ao bot

## 🔧 Configurações Avançadas

### Multioperadores

Para habilitar suporte a múltiplos operadores:

1. Descomente o relacionamento no modelo `ChatSession`:
   ```php
   public function operator()
   {
       return $this->belongsTo(User::class);
   }
   ```

2. Execute a migration adicional:
   ```bash
   php artisan make:migration add_operator_id_to_chat_sessions_table
   ```

### Notificações em Tempo Real

Para notificações em tempo real usando WebSockets:

1. Instale o Laravel Echo e Pusher:
   ```bash
   composer require pusher/pusher-php-server
   npm install laravel-echo pusher-js
   ```

2. Configure no `.env`:
   ```ini
   BROADCAST_DRIVER=pusher
   PUSHER_APP_ID=your_app_id
   PUSHER_APP_KEY=your_app_key
   PUSHER_APP_SECRET=your_app_secret
   ```

### Logs e Monitoramento

Monitore as interações com:

```bash
tail -f storage/logs/laravel.log
```

Para dashboard de filas:
```bash
composer require laravel/horizon
php artisan horizon
```

## 🤝 Contribuindo

1. Faça um Fork do projeto
2. Crie sua Branch (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a Branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Soon...

## ✉️ Contato
 
Link do Projeto: [HdfFlow](https://github.com/Sertoriel/handoffflow)

---

**HandoffFlow** © 2023  