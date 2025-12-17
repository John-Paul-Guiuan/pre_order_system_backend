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

# Copy composer files first (better caching)
COPY composer.json composer.lock ./

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copy the rest of the project
COPY . .

# Create required Laravel directories with proper permissions
RUN mkdir -p storage/framework/{cache,data,sessions,views} \
    storage/app/public \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Cache config, routes, and views for production
RUN php artisan config:clear \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Run migrations automatically (force)
RUN php artisan migrate --force || echo "Migration failed, check database connection"

# Expose Render port
EXPOSE 8080

# Start Laravel server using Render PORT
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
