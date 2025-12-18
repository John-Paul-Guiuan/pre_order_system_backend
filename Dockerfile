# ===============================
# Laravel Backend – Render (Free Tier)
# PHP 8.2 + PostgreSQL + Auto Migrate
# ===============================

FROM php:8.2-cli

# -------------------------------
# Install system dependencies
# -------------------------------
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        mbstring \
        bcmath \
        gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# -------------------------------
# Set working directory
# -------------------------------
WORKDIR /var/www/html

# -------------------------------
# Install Composer
# -------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# -------------------------------
# Copy Laravel project files
# -------------------------------
COPY . .

# -------------------------------
# Install PHP dependencies
# -------------------------------
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev

# -------------------------------
# Fix Laravel permissions
# -------------------------------
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# -------------------------------
# Expose Render port
# -------------------------------
EXPOSE 8080

# -------------------------------
# START CONTAINER
# - Run migrations automatically
# - Then start Laravel server
# -------------------------------
CMD php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080
