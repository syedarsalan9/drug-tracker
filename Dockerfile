# ============================
# Stage 1 — Composer Install
# ============================
FROM composer:2 AS composer_stage
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-ansi --no-scripts

COPY . .
RUN composer dump-autoload --optimize


# ============================
# Stage 2 — Build PHP + Nginx
# ============================
FROM php:8.3-fpm

# Install required extensions + system deps
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    zip \
    supervisor \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring xml gd

WORKDIR /var/www/html

# Copy Laravel app from composer build stage
COPY --from=composer_stage /app ./

# Copy Laravel nginx config
COPY ./deploy/nginx.conf /etc/nginx/nginx.conf

# Supervisor config to run PHP-FPM + nginx
COPY ./deploy/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Expose port (Railway auto-detects)
EXPOSE 8080

CMD ["/usr/bin/supervisord"]
