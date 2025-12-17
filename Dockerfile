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
    && docker-php-ext-install pdo pdo_pgsql mbstring bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# ✅ Install Composer FIRST (before using it)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ✅ Copy entire Laravel project (artisan included)
COPY . .

# ✅ Install dependencies AFTER artisan exists
RUN composer install --no-interaction --optimize-autoloader --no-dev

# ✅ Create required Laravel directories & permissions
RUN mkdir -p storage/framework/{cache,data,sessions,views} \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Expose Render port
EXPOSE 8080

# ✅ Start Laravel using Render's PORT
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
