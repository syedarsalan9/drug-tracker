FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    zip unzip default-mysql-client curl \
    && docker-php-ext-install pdo_mysql mbstring \
    && apt-get clean

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chmod -R 777 storage bootstrap/cache

HEALTHCHECK --interval=30s --timeout=3s --start-period=40s \
  CMD curl -f http://localhost:8000/api/test || exit 1

EXPOSE 8000