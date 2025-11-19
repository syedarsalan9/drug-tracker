# ---------- Stage 1: Build PHP dependencies (vendor) ----------
FROM composer:2 AS vendor

WORKDIR /app

# Copy only composer files first (better caching)
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev, no scripts)
RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --no-interaction \
    --no-progress

# Now copy full project
COPY . .

# Re-run to install anything that needs full source (if any)
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress


# ---------- Stage 2: Runtime image ----------
FROM php:8.3-cli

# Set working directory
WORKDIR /var/www/html

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Copy application code and vendor from builder
COPY --from=vendor /app ./

# Ensure storage + cache directories exist and set permissions
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Railway exposes its own PORT env.
# Locally: defaults to 8000 if PORT not set.
EXPOSE 8000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
