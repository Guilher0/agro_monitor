# ─── Estágio base: dependências do sistema e extensões PHP ─────────────────────
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    bash \
    curl \
    freetype-dev \
    git \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    mysql-client \
    nginx \
    oniguruma-dev \
    openssl \
    supervisor \
    unzip \
    zip

RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        pcntl \
        zip

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/opcache.ini    /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php-fpm.conf   /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx/nginx.conf   /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh      /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

# ─── Estágio de desenvolvimento: código via volume, deps pré-instaladas ────────
FROM base AS dev

# Node disponível no container de app para o caso de precisar rodar comandos npm
RUN apk add --no-cache nodejs npm

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# ─── Estágio de produção: copia código e compila assets ────────────────────────
FROM base AS prod

RUN apk add --no-cache nodejs npm

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm ci \
    && npm run build \
    && rm -rf node_modules \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
