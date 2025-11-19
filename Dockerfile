FROM php:8.2-cli

WORKDIR /var/www

# Install dependencies in one layer
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chmod -R 777 storage bootstrap/cache

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s \
  CMD curl -f http://localhost:${PORT:-8000}/api/test || exit 1

EXPOSE 8000

# Start server
CMD php artisan migrate --force && \
    php artisan config:clear && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}