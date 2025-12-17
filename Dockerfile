# Use PHP 8.2 CLI image (lightweight for Laravel)
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

# Copy all project files
COPY . .

# Create required Laravel directories with proper permissions
RUN mkdir -p storage/framework/{cache,data,sessions,views} \
    storage/app/public \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose Render port (Render will override with $PORT)
EXPOSE 8080

# Use entrypoint to handle migrations, caching, and start server
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
