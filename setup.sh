#!/bin/bash

# Ilumina - Script de Setup Automático
# Este script facilita a instalação e execução do sistema Ilumina no localhost

set -e

echo "🚀 Iniciando setup do sistema Ilumina..."
echo "=================================="

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para mostrar mensagens
show_message() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

show_success() {
    echo -e "${GREEN}[SUCESSO]${NC} $1"
}

show_warning() {
    echo -e "${YELLOW}[AVISO]${NC} $1"
}

show_error() {
    echo -e "${RED}[ERRO]${NC} $1"
}

# Verificar se o PHP está instalado
check_php() {
    show_message "Verificando instalação do PHP..."
    if command -v php >/dev/null 2>&1; then
        PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        show_success "PHP $PHP_VERSION detectado"
        
        # Verificar versão mínima
        if [ "$(printf '%s\n' "7.4" "$PHP_VERSION" | sort -V | head -n1)" = "7.4" ]; then
            show_success "Versão do PHP é compatível (≥7.4)"
        else
            show_error "PHP 7.4 ou superior é necessário. Versão atual: $PHP_VERSION"
            echo "Por favor, atualize seu PHP antes de continuar."
            exit 1
        fi
    else
        show_error "PHP não encontrado. Por favor, instale o PHP 7.4 ou superior."
        echo "Ubuntu/Debian: sudo apt install php php-cli php-json php-mbstring"
        echo "CentOS/RHEL: sudo yum install php php-cli php-json php-mbstring"
        echo "macOS: brew install php"
        exit 1
    fi
}

# Verificar se o Composer está instalado
check_composer() {
    show_message "Verificando instalação do Composer..."
    if command -v composer >/dev/null 2>&1; then
        COMPOSER_VERSION=$(composer --version | head -n1)
        show_success "Composer detectado: $COMPOSER_VERSION"
    else
        show_warning "Composer não encontrado. Tentando instalar automaticamente..."
        
        # Tentar instalar o composer
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php composer-setup.php --quiet
        php -r "unlink('composer-setup.php');"
        
        if [ -f composer.phar ]; then
            sudo mv composer.phar /usr/local/bin/composer
            sudo chmod +x /usr/local/bin/composer
            show_success "Composer instalado com sucesso!"
        else
            show_error "Falha ao instalar o Composer automaticamente."
            echo "Por favor, instale manualmente: https://getcomposer.org/download/"
            exit 1
        fi
    fi
}

# Configurar ambiente
setup_environment() {
    show_message "Configurando ambiente..."
    
    # Copiar .env.example para .env se não existir
    if [ ! -f .env ]; then
        cp .env.example .env
        show_success "Arquivo .env criado a partir do .env.example"
    else
        show_warning "Arquivo .env já existe. Mantendo configurações atuais."
    fi
}

# Instalar dependências PHP
install_dependencies() {
    show_message "Instalando dependências PHP..."
    
    # Verificar se vendor existe
    if [ ! -d "vendor" ]; then
        show_message "Executando composer install..."
        
        # Tentar instalar sem interação
        if composer install --no-dev --optimize-autoloader --quiet 2>/dev/null; then
            show_success "Dependências instaladas com sucesso!"
        else
            show_warning "Falha ao instalar dependências via Composer."
            show_warning "O sistema funcionará em modo simples (sem todas as funcionalidades)."
            show_message "Para instalar as dependências manualmente: composer install"
        fi
    else
        show_success "Dependências já estão instaladas."
    fi
}

# Verificar banco de dados (MySQL/MariaDB)
check_database() {
    show_message "Verificando banco de dados..."
    
    if command -v mysql >/dev/null 2>&1; then
        show_success "MySQL/MariaDB detectado"
        
        echo
        show_message "Para configurar o banco de dados:"
        echo "1. Crie um banco chamado 'ilumina':"
        echo "   mysql -u root -p -e \"CREATE DATABASE ilumina CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
        echo
        echo "2. Execute o schema:"
        echo "   mysql -u root -p ilumina < database/schema.sql"
        echo
        echo "3. Configure as credenciais no arquivo .env"
        echo
    else
        show_warning "MySQL/MariaDB não detectado."
        echo "O sistema funcionará em modo simples sem persistência de dados."
        echo
        echo "Para instalar MySQL:"
        echo "Ubuntu/Debian: sudo apt install mysql-server"
        echo "CentOS/RHEL: sudo yum install mysql-server"
        echo "macOS: brew install mysql"
    fi
}

# Função para iniciar o servidor
start_server() {
    show_message "Iniciando servidor de desenvolvimento..."
    
    # Verificar se a porta 8000 está livre
    if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null 2>&1; then
        show_warning "Porta 8000 já está em uso. Tentando parar processos existentes..."
        pkill -f "php -S.*:8000" 2>/dev/null || true
        sleep 2
    fi
    
    echo
    show_success "Servidor iniciado com sucesso!"
    echo
    echo "🌐 URLs disponíveis:"
    echo "   Frontend (PWA): http://localhost:8000"
    echo "   API Health:     http://localhost:8000/health"
    echo "   API Docs:       http://localhost:8000/api/v1/"
    echo
    echo "💡 Para parar o servidor: Ctrl+C"
    echo
    
    # Iniciar servidor
    php -S localhost:8000 -t public
}

# Função principal
main() {
    echo
    echo "Sistema PWA para gestão da iluminação pública"
    echo "Permite que cidadãos relatem problemas e gestores acompanhem via mapa, kanban e KPIs"
    echo
    
    # Verificações do sistema
    check_php
    check_composer
    
    # Setup do projeto
    setup_environment
    install_dependencies
    check_database
    
    echo
    show_success "Setup concluído com sucesso! 🎉"
    echo
    
    # Perguntar se deve iniciar o servidor
    read -p "Deseja iniciar o servidor agora? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        start_server
    else
        echo
        show_message "Para iniciar o servidor manualmente:"
        echo "   ./setup.sh --start"
        echo "   ou"
        echo "   composer serve"
        echo "   ou"
        echo "   php -S localhost:8000 -t public"
        echo
    fi
}

# Verificar argumentos da linha de comando
if [ "$1" = "--start" ] || [ "$1" = "-s" ]; then
    start_server
elif [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Uso: $0 [opções]"
    echo
    echo "Opções:"
    echo "  --start, -s    Apenas iniciar o servidor"
    echo "  --help, -h     Mostrar esta ajuda"
    echo
    echo "Sem argumentos: Executar setup completo"
    exit 0
else
    main
fi