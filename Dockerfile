# Use official PHP 8.2 CLI image
FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy ALL project files first (artisan MUST exist)
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Create Laravel writable directories
RUN mkdir -p storage/framework/{cache,data,sessions,views} \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Expose Render port
EXPOSE 8080

# IMPORTANT:
# - No config:cache
# - No route:cache
# - No migrate at build time
# - Let Render inject env vars at runtime

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
