#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}" )/.." && pwd)"
cd "$PROJECT_ROOT_DIR"

USE_DOCKER=1
START_APP=1
NON_INTERACTIVE=0
AUTO_INSTALL=0 # Novo: instala dependências no modo --no-docker (Ubuntu/Debian)

print_header() {
  echo "\n========================================"
  echo " $1"
  echo "========================================\n"
}

usage() {
  cat <<EOF
Instalador do projeto (Laravel API Produtos/Pedidos)

Uso: scripts/install.sh [opções]
  --no-docker       Não iniciar serviços Docker (usa serviços já rodando)
  --auto-install    (Ubuntu/Debian) Instala dependências locais no modo --no-docker
  --yes             Não perguntar nada (modo não interativo)
  -h, --help        Mostrar ajuda
EOF
}

for arg in "$@"; do
  case "$arg" in
    --no-docker) USE_DOCKER=0 ; shift ;;
    --auto-install) AUTO_INSTALL=1 ; shift ;;
    --yes) NON_INTERACTIVE=1 ; shift ;;
    -h|--help) usage ; exit 0 ;;
    *) echo "Opção inválida: $arg" ; usage ; exit 1 ;;
  esac
done

# Helpers
need() { command -v "$1" >/dev/null 2>&1 || { echo "Erro: '$1' não encontrado."; return 1; }; }
confirm() { [[ "$NON_INTERACTIVE" -eq 1 ]] && return 0; read -r -p "$1 [s/N] " ans; [[ "${ans,,}" == "s" ]]; }

# 1) Checagens básicas
print_header "1) Checando dependências"

if [[ "$USE_DOCKER" -eq 1 ]]; then
  if command -v docker compose >/dev/null 2>&1; then
    DOCKER_COMPOSE="docker compose"
  elif command -v docker-compose >/dev/null 2>&1; then
    DOCKER_COMPOSE="docker-compose"
  else
    echo "Erro: docker compose não encontrado. Instale Docker Compose ou use --no-docker."; exit 1
  fi
fi

# 1.1) Modo --no-docker com --auto-install (Ubuntu/Debian)
if [[ "$USE_DOCKER" -eq 0 && "$AUTO_INSTALL" -eq 1 ]]; then
  print_header "1.1) Auto-instalação (Ubuntu/Debian)"
  if ! command -v apt-get >/dev/null 2>&1; then
    echo "Auto-instalação suportada apenas em Ubuntu/Debian."; exit 1
  fi
  if [[ "$EUID" -ne 0 ]]; then
    SUDO="sudo"
  else
    SUDO=""
  fi

  echo "Atualizando índices de pacotes..."
  $SUDO apt-get update -y

  # PHP + extensões e Composer
  $SUDO apt-get install -y php php-cli php-mbstring php-xml php-curl php-zip php-mysql unzip curl git
  if ! command -v composer >/dev/null 2>&1; then
    echo "Instalando Composer..."
    curl -sS https://getcomposer.org/installer -o composer-setup.php
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f composer-setup.php
  fi

  # Node.js + npm (NodeSource LTS)
  if ! command -v node >/dev/null 2>&1; then
    echo "Instalando Node.js LTS..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | $SUDO -E bash -
    $SUDO apt-get install -y nodejs
  fi

  # Redis
  if ! command -v redis-server >/dev/null 2>&1; then
    echo "Instalando Redis..."
    $SUDO apt-get install -y redis-server
    $SUDO systemctl enable redis-server || true
    $SUDO systemctl start redis-server || true
  fi

  # MySQL Server
  if ! command -v mysql >/dev/null 2>&1; then
    echo "Instalando MySQL Server..."
    DEBIAN_FRONTEND=noninteractive $SUDO apt-get install -y mysql-server
    $SUDO systemctl enable mysql || true
    $SUDO systemctl start mysql || true
  fi

  print_header "1.2) Configurando MySQL (porta 3307, usuário 'laravel')"
  MYSQL_CNF="/etc/mysql/mysql.conf.d/mysqld.cnf"
  if [[ -f "$MYSQL_CNF" ]]; then
    # Bind address padrão (permanece 127.0.0.1) e porta alternativa 3307
    $SUDO sed -i 's/^#\?port\s*=.*/port = 3307/' "$MYSQL_CNF" || true
    $SUDO systemctl restart mysql || true
  fi

  echo "Criando banco, usuário e permissões (se não existirem)..."
  MYSQL_PWD="" mysql -uroot -e "CREATE DATABASE IF NOT EXISTS api_produtos_pedidos;" || true
  MYSQL_PWD="" mysql -uroot -e "CREATE DATABASE IF NOT EXISTS api_produtos_pedidos_test;" || true
  MYSQL_PWD="" mysql -uroot -e "CREATE USER IF NOT EXISTS 'laravel'@'localhost' IDENTIFIED BY 'secret';" || true
  MYSQL_PWD="" mysql -uroot -e "GRANT ALL PRIVILEGES ON api_produtos_pedidos.* TO 'laravel'@'localhost';" || true
  MYSQL_PWD="" mysql -uroot -e "GRANT ALL PRIVILEGES ON api_produtos_pedidos_test.* TO 'laravel'@'localhost';" || true
  MYSQL_PWD="" mysql -uroot -e "FLUSH PRIVILEGES;" || true
  echo "Banco de testes criado: api_produtos_pedidos_test"

  echo "MySQL e Redis prontos."
fi

# 2) Preparando .env
print_header "2) Preparando .env"
if [[ ! -f .env ]]; then
  cp .env.example .env || true
  echo "Arquivo .env criado a partir de .env.example"
else
  echo ".env já existe, mantendo como está"
fi

# 3) Subir serviços Docker (db/redis e app) OU preparar local
if [[ "$USE_DOCKER" -eq 1 ]]; then
  print_header "3) Subindo serviços Docker (db, redis, app, frontend)"
  $DOCKER_COMPOSE up -d db redis app frontend

  print_header "3.0) Aguardando MySQL ficar pronto (container db)"
  until $DOCKER_COMPOSE exec -T db mysqladmin ping -h"127.0.0.1" -uroot -proot --silent >/dev/null 2>&1; do
    echo -n "."; sleep 1;
  done
  echo "\nMySQL pronto."

  print_header "3.0.1) Criando banco de dados de testes"
  $DOCKER_COMPOSE exec -T db mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS api_produtos_pedidos_test; GRANT ALL PRIVILEGES ON api_produtos_pedidos_test.* TO 'laravel'@'%'; FLUSH PRIVILEGES;" || true
  echo "Banco de testes criado: api_produtos_pedidos_test"

  print_header "3.1) Ajustando .env para Docker"
  sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
  sed -i 's/^DB_HOST=.*/DB_HOST=db/' .env
  sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
  sed -i 's/^DB_DATABASE=.*/DB_DATABASE=api_produtos_pedidos/' .env
  sed -i 's/^DB_USERNAME=.*/DB_USERNAME=laravel/' .env
  sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=secret/' .env
  sed -i 's/^CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env || echo 'CACHE_DRIVER=redis' >> .env
  sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/' .env || echo 'REDIS_HOST=redis' >> .env
else
  print_header "3) Ambiente local (sem Docker)"
  sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
  sed -i 's/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
  sed -i 's/^DB_PORT=.*/DB_PORT=3307/' .env
  sed -i 's/^DB_DATABASE=.*/DB_DATABASE=api_produtos_pedidos/' .env
  sed -i 's/^DB_USERNAME=.*/DB_USERNAME=laravel/' .env
  sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=secret/' .env
  sed -i 's/^CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env || echo 'CACHE_DRIVER=redis' >> .env
  sed -i 's/^REDIS_HOST=.*/REDIS_HOST=127.0.0.1/' .env || echo 'REDIS_HOST=127.0.0.1' >> .env
fi

# 4) Composer install
print_header "4) Instalando dependências (composer install)"
if [[ "$USE_DOCKER" -eq 1 ]]; then
  $DOCKER_COMPOSE exec -T app composer install --no-interaction --prefer-dist || true
else
  composer install --no-interaction --prefer-dist
fi

# 5) Key + Migrations + Seed
print_header "5) Configurando aplicação (API Laravel)"
php -r 'file_exists(".env") || copy(".env.example", ".env");'

if [[ "$USE_DOCKER" -eq 1 ]]; then
  # Criar diretórios do storage e ajustar permissões (necessário para Laravel)
  print_header "5.0) Preparando diretórios do storage e permissões"
  # mkdir -p é idempotente: não causa erro se diretórios já existirem
  $DOCKER_COMPOSE exec -T app mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/framework/testing storage/logs storage/api-docs bootstrap/cache || true
  $DOCKER_COMPOSE exec -T app chmod -R 775 storage bootstrap/cache 2>/dev/null || true
  $DOCKER_COMPOSE exec -T app chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
  
  $DOCKER_COMPOSE exec -T app php artisan key:generate --ansi || true
  $DOCKER_COMPOSE exec -T app php artisan migrate --force || true
  $DOCKER_COMPOSE exec -T app php artisan db:seed --class=DefaultUserSeeder || true
  # Swagger docs (no container)
  $DOCKER_COMPOSE exec -T app php artisan l5-swagger:generate || true
else
  # Criar diretórios do storage localmente (se não existirem)
  # mkdir -p é idempotente: não causa erro se diretórios já existirem
  mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/framework/testing storage/logs storage/api-docs bootstrap/cache || true
  chmod -R 775 storage bootstrap/cache 2>/dev/null || true
  
  php artisan key:generate --ansi || true
  php artisan migrate --force
  php artisan db:seed --class=DefaultUserSeeder || true
  # Swagger docs local
  php artisan l5-swagger:generate || true
fi

# 5.1) Iniciar API local quando sem Docker
if [[ "$USE_DOCKER" -eq 0 ]]; then
  print_header "5.1) Iniciando API local (php artisan serve em background)"
  pkill -f "php artisan serve" >/dev/null 2>&1 || true
  nohup php artisan serve --host=0.0.0.0 --port=8000 > storage/logs/dev-server.log 2>&1 &
  echo "API iniciada em http://localhost:8000 (log: storage/logs/dev-server.log)"
fi

# 5.2) Frontend (Vue)
print_header "5.2) Preparando Frontend (Vue)"
if [[ "$USE_DOCKER" -eq 1 ]]; then
  echo "Frontend container em http://localhost:3000"
else
  FRONT_DIR="${PROJECT_ROOT_DIR%/api-produtos-pedidos}/frontend-vue"
  if [[ -d "$FRONT_DIR" ]]; then
    if command -v node >/dev/null 2>&1 && command -v npm >/dev/null 2>&1; then
      pushd "$FRONT_DIR" >/dev/null
      npm install --silent
      pkill -f "vite" >/dev/null 2>&1 || true
      nohup npm run dev -- --host > vite-dev.log 2>&1 &
      echo "Frontend em http://localhost:3000 (log: frontend-vue/vite-dev.log)"
      popd >/dev/null
    else
      echo "Aviso: Node/npm não encontrados. Pulei a inicialização do frontend."
    fi
  else
    echo "Aviso: diretório do frontend não encontrado."
  fi
fi

# 5.3) Verificar API do container
if [[ "$USE_DOCKER" -eq 1 ]]; then
  print_header "5.3) Verificando API (http://localhost:8000)"
  # Testa endpoint de API real (não a raiz que pode retornar 404)
  if curl -sSf "http://localhost:8000/api/produtos" >/dev/null; then
    echo "✓ API respondendo corretamente"
  else
    echo "Aviso: API respondeu com erro; verifique logs com: docker compose logs app"
  fi
  echo "Swagger UI: http://localhost:8000/api/documentation"
fi

# 6) Resumo e próximos passos
print_header "6) Finalizado!"
APP_URL="http://localhost:8000"
DB_LINE="MySQL -> 127.0.0.1:3307 | db=api_produtos_pedidos | user=laravel | pass=secret"
echo "- Ambiente pronto."
if [[ "$USE_DOCKER" -eq 1 ]]; then
  echo "- API (container): $APP_URL"
  echo "- Frontend (container): http://localhost:3000"
else
  echo "- API (local): $APP_URL"
  echo "- Frontend (local via Vite): http://localhost:3000"
fi
echo "- Swagger UI: http://localhost:8000/api/documentation"
echo "- Banco de dados: $DB_LINE"
echo "- Banco de testes: api_produtos_pedidos_test (isolado para testes)"
echo "- Redis: 127.0.0.1:6379"
echo "- Usuário de teste (seeder): tester@example.com / password123"
echo "- Testes: php artisan test (usa banco de testes automaticamente)"

exit 0


