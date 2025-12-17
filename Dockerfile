# ===============================
# Laravel Dockerfile for Render
# ===============================

# Use official PHP 8.2 CLI image
FROM php:8.2-cli

# -------------------------------
# Install system dependencies
# -------------------------------
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

# -------------------------------
# Set working directory
# -------------------------------
WORKDIR /var/www/html

# -------------------------------
# Copy all project files
# -------------------------------
COPY . .

# -------------------------------
# Install Composer
# -------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# -------------------------------
# Install PHP dependencies
# -------------------------------
RUN composer install --no-interaction --optimize-autoloader --no-dev

# -------------------------------
# Create Laravel storage and cache directories with correct permissions
# -------------------------------
RUN mkdir -p storage/framework/{cache,data,sessions,views} \
    storage/app/public \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# -------------------------------
# Cache Laravel config, routes, and views
# -------------------------------
RUN php artisan config:clear \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# -------------------------------
# Run database migrations automatically
# -------------------------------
# Force migration; will not break build if DB is not ready
RUN php artisan migrate --force || echo "Database migration failed. Check DB connection."

# -------------------------------
# Expose Render port (Render uses PORT env var)
# -------------------------------
EXPOSE 8080

# -------------------------------
# Start Laravel using Render's PORT
# -------------------------------
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
