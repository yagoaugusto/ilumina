# Ilumina 💡

Sistema PWA para gestão da iluminação pública - Permite que cidadãos abram chamados por telefone, foto e localização, enquanto gestores monitoram através de mapa, kanban, KPIs e equipes, com prazos SLA e notificações automáticas.

## 🚀 Funcionalidades

### Para Cidadãos
- 📱 Interface PWA responsiva e offline
- 📍 Captura automática de localização GPS
- 📷 Upload de fotos para documentar problemas
- 📞 Registro de dados de contato
- 🎫 Geração de protocolo de atendimento

### Para Gestores
- 🗺️ Mapa interativo com visualização de chamados
- 📊 Dashboard com KPIs em tempo real
- 📋 Kanban para gestão de status dos chamados
- 👥 Gerenciamento de equipes
- ⏰ Controle de SLA e prazos
- 🔔 Sistema de notificações automáticas

## 🛠️ Tecnologias

- **Backend**: PHP 7.4.33 + Slim Framework 4
- **Database**: MySQL 8.0
- **Frontend**: HTML5 + Tailwind CSS + Leaflet.js
- **PWA**: Service Worker + Web App Manifest
- **API**: RESTful architecture

## 📁 Estrutura do Projeto

```
ilumina/
├── app/
│   ├── Config/          # Configurações de banco de dados
│   ├── Controllers/     # Controladores da API
│   ├── Models/          # Modelos Eloquent
│   └── Services/        # Serviços de negócio
├── routes/
│   └── api.php          # Definição das rotas da API
├── public/
│   └── index.php        # Ponto de entrada da aplicação
├── frontend/
│   ├── index.html       # Interface PWA
│   ├── manifest.json    # Configuração PWA
│   ├── sw.js           # Service Worker
│   └── assets/
│       ├── css/        # Estilos
│       └── js/         # JavaScript
├── database/
│   ├── schema.sql      # Esquema do banco de dados
│   ├── migrations/     # Migrações
│   └── seeds/          # Dados iniciais
└── composer.json       # Dependências PHP
```

## 🔧 Instalação Rápida

### Setup Automático (Recomendado) 🚀

```bash
# Clone o repositório
git clone https://github.com/yagoaugusto/ilumina.git
cd ilumina

# Execute o setup automático
./setup.sh
```

O script irá:
- ✅ Verificar PHP e Composer
- ✅ Instalar dependências automaticamente
- ✅ Configurar ambiente (.env)
- ✅ Verificar banco de dados
- ✅ Iniciar o servidor

### Setup com Docker 🐳

Para uma instalação ainda mais simples:

```bash
# Clone o repositório
git clone https://github.com/yagoaugusto/ilumina.git
cd ilumina

# Inicie com Docker Compose
docker-compose up -d

# Acesse: http://localhost:8000
```

### Setup Manual (se preferir)

<details>
<summary>👈 Clique para ver instruções manuais</summary>

#### Pré-requisitos
- PHP 7.4+ (recomendado 8.0+)
- MySQL 8.0+ (opcional)
- Composer

#### Passo a passo

1. **Clone e instale**
```bash
git clone https://github.com/yagoaugusto/ilumina.git
cd ilumina
composer install --no-dev
```

2. **Configure ambiente**
```bash
cp .env.example .env
# Edite .env se necessário
```

3. **Banco de dados (opcional)**
```bash
mysql -u root -p -e "CREATE DATABASE ilumina CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p ilumina < database/schema.sql
```

4. **Inicie o servidor**
```bash
php -S localhost:8000 -t public
```

</details>

### 📱 Acesso à Aplicação

Após o setup:
- **Frontend PWA**: http://localhost:8000
- **API Health**: http://localhost:8000/health
- **API Endpoints**: http://localhost:8000/api/v1/*

### ⚡ Inicialização Rápida

Depois da primeira instalação, para iniciar rapidamente:

```bash
# Método 1: Script de setup
./setup.sh --start

# Método 2: Composer
composer serve

# Método 3: PHP direto
php -S localhost:8000 -t public

# Método 4: Docker
docker-compose up -d
```

## 📡 API Endpoints

### Health Check
- `GET /health` - Verificação de status da API

### Tickets (Chamados)
- `GET /api/v1/tickets` - Listar todos os chamados
- `POST /api/v1/tickets` - Criar novo chamado
- `GET /api/v1/tickets/{id}` - Obter chamado específico
- `PUT /api/v1/tickets/{id}` - Atualizar chamado
- `DELETE /api/v1/tickets/{id}` - Excluir chamado

### Teams (Equipes)
- `GET /api/v1/teams` - Listar equipes
- `POST /api/v1/teams` - Criar nova equipe

### Users (Usuários)
- `GET /api/v1/users` - Listar usuários
- `POST /api/v1/users` - Criar novo usuário

### KPIs
- `GET /api/v1/kpis` - Obter indicadores de performance

## 📱 PWA Features

A aplicação está configurada como Progressive Web App com:

- 📱 **Instalável**: Pode ser instalada em dispositivos móveis
- 🔄 **Cache Offline**: Funciona sem conexão com internet
- 📱 **Responsiva**: Se adapta a diferentes tamanhos de tela
- 🚀 **Performance**: Carregamento rápido com cache inteligente

## 🧪 Teste da API

```bash
# Verificar saúde da API
curl http://localhost:8000/health

# Criar um novo chamado
curl -X POST http://localhost:8000/api/v1/tickets \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Poste sem iluminação",
    "description": "Poste na esquina da Rua A com Rua B está sem iluminação há 3 dias",
    "citizen_name": "João Silva",
    "citizen_phone": "(11) 99999-9999",
    "latitude": -23.5505,
    "longitude": -46.6333,
    "priority": "high"
  }'
```

## 🗄️ Banco de Dados

### Estrutura Principal

- **tickets**: Armazena os chamados dos cidadãos
- **teams**: Equipes de manutenção
- **users**: Usuários do sistema (gestores, técnicos)
- **ticket_comments**: Histórico de atualizações dos chamados

### Usuários Padrão

Após executar o schema, os seguintes usuários estarão disponíveis:

- **Admin**: admin@ilumina.local (senha: password)
- **Gestor Norte**: gestor.norte@ilumina.local (senha: password)
- **Técnico**: joao@ilumina.local (senha: password)

## 🔐 Segurança

- Senhas hashadas com bcrypt
- Validação de entrada nos endpoints
- Headers CORS configurados
- Proteção contra SQL injection via Eloquent ORM

## 📈 Monitoramento

O sistema inclui endpoints para monitoramento:
- Status da API via `/health`
- KPIs operacionais via `/api/v1/kpis`
- Logs de aplicação configuráveis

## 🤝 Contribuição

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 🆘 Suporte

Para suporte e dúvidas:
- Abra uma issue no GitHub
- Contate a equipe de desenvolvimento

---

Desenvolvido com ❤️ para melhorar a gestão da iluminação pública