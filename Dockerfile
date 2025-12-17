# Use PHP 8.2 FPM image
FROM php:8.2-fpm

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
    && docker-php-ext-install pdo_pgsql mbstring bcmath gd

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader

# Create required Laravel directories
RUN mkdir -p storage/framework/{cache,data,sessions,views} bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

# Cache config and routes for production
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

# Expose the port
EXPOSE 9000

# Start PHP-FPM (production)
CMD ["php-fpm"]
