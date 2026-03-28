# ──────────────────────────────────────────
# Stage 1: base — общее для dev и prod
# ──────────────────────────────────────────
FROM php:8.5-fpm-alpine AS base

# Системные зависимости
RUN apk update && apk add --no-cache \
    bash \
    curl \
    zip unzip \
    libpq-dev \
    libzip-dev \
    icu-dev \
    icu-libs \
    icu-data-full \
    zlib-dev \
    g++ \
    make \
    autoconf \
    linux-headers

RUN docker-php-ext-configure intl \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        zip \
        bcmath \
        intl \
        pcntl

RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Пользователь (совпадает с хостовым UID чтобы не было проблем с правами)
ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} appgroup && \
    adduser -u ${UID} -G appgroup -s /bin/bash -D appuser

RUN mkdir -p /var/log/php-fpm /var/log/php && \
    chown -R appuser:appgroup /var/log/php-fpm /var/log/php

WORKDIR /var/www

# ──────────────────────────────────────────
# Stage 2: development
# ──────────────────────────────────────────
FROM base AS development

# Xdebug только в dev
RUN pecl install xdebug && docker-php-ext-enable xdebug

# dev-конфиг php (более мягкие лимиты, verbose ошибки)
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/99-dev.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

USER appuser

# ──────────────────────────────────────────
# Stage 3: production
# ──────────────────────────────────────────
FROM base AS production

COPY docker/php/php-prod.ini /usr/local/etc/php/conf.d/99-prod.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Копируем код и устанавливаем зависимости
COPY --chown=appuser:appgroup src/ .
RUN composer install --no-dev --optimize-autoloader --no-interaction

USER appuser