#!/bin/sh
set -e

# Em ambiente com volume bind, o diretório vendor pode iniciar vazio.
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "Dependências PHP ausentes. Instalando via Composer..."
    composer install --no-interaction --prefer-dist
fi

# Garante .env inicial para comandos artisan que escrevem/leem arquivo de ambiente.
if [ ! -f /var/www/html/.env ] && [ -f /var/www/html/.env.example ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Aguarda o MySQL estar disponível antes de continuar
echo "Aguardando MySQL..."
until php artisan db:show --no-interaction > /dev/null 2>&1; do
    sleep 2
done
echo "MySQL disponível."

# Gera chave da aplicação se não existir
php artisan key:generate --no-interaction --force 2>/dev/null || true

# Garante diretórios e permissões de escrita para compilação de views/cache em ambiente docker.
mkdir -p /var/www/html/storage/framework/{cache,sessions,views} /var/www/html/bootstrap/cache
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Executa as migrations do banco central (landlord)
php artisan migrate --force --no-interaction || \
    echo "Aviso: migration falhou no bootstrap; seguindo inicialização do container."

# Linka o storage público
php artisan storage:link --no-interaction 2>/dev/null || true

# Limpa apenas caches seguros para evitar dependência de tabela `cache` em dev.
php artisan config:clear --no-interaction 2>/dev/null || true
php artisan route:clear --no-interaction 2>/dev/null || true
php artisan view:clear --no-interaction 2>/dev/null || true

exec "$@"
