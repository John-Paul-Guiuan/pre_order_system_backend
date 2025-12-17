FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip unzip git curl libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring bcmath gd

WORKDIR /var/www/html

COPY . .

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader

RUN mkdir -p storage/framework/{cache,data,sessions,views} bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 8080

CMD php artisan config:clear \
 && php artisan route:clear \
 && php artisan view:clear \
 && php -S 0.0.0.0:$PORT -t public
