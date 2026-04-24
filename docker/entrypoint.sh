#!/bin/sh
set -e

# Aguarda o MySQL estar disponível antes de continuar
echo "Aguardando MySQL..."
until php artisan db:show --no-interaction > /dev/null 2>&1; do
    sleep 2
done
echo "MySQL disponível."

# Gera chave da aplicação se não existir
php artisan key:generate --no-interaction --force 2>/dev/null || true

# Executa as migrations do banco central (landlord)
php artisan migrate --force --no-interaction

# Linka o storage público
php artisan storage:link --no-interaction 2>/dev/null || true

# Limpa e aquece os caches
php artisan optimize --no-interaction 2>/dev/null || true

exec "$@"
