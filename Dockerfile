FROM php:8.2-cli

# System dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring bcmath gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Install Composer dependencies
COPY composer.json composer.lock ./
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copy application code
COPY . .

# Copy entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose Render port
EXPOSE 8080

# Entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
