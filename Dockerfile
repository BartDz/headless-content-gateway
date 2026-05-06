FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    git \
    curl \
    unzip \
    autoconf \
    g++ \
    make \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-install \
    zip \
    intl \
    opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

FROM base AS deps

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

FROM base AS prod

COPY --from=deps /app/vendor /app/vendor
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php bin/console cache:warmup --env=prod 2>/dev/null || true

COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

EXPOSE 9000
CMD ["php-fpm"]
