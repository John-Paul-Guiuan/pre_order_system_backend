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

# Copy composer files first for caching
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

# Run database migrations automatically (force)
# If database not ready, it will try again on container restart
RUN php artisan migrate --force || echo "Database migration failed. Check DB connection."

# Expose Render port (Render uses PORT env var)
EXPOSE 8080

# Start Laravel using Render's PORT environment variable
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
