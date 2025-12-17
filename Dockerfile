# Use PHP 8.2 CLI
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

# Copy the entire project (artisan must exist now)
COPY . .

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies (artisan exists, post-autoload scripts succeed)
RUN composer install --no-interaction --optimize-autoloader --no-dev

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

# Run database migrations (force)
# It will fail gracefully if DB not ready
RUN php artisan migrate --force || echo "Database migration failed. Check DB connection."

# Expose Render port
EXPOSE 8080

# Copy entrypoint script and make executable
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Use entrypoint to handle initialization and server start
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Default command (can be overridden)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=${PORT:-8080}"]
