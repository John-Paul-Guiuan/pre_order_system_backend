#!/bin/bash
set -e

# Get port from Render's PORT environment variable, default to 8080
PORT=${PORT:-8080}

echo "Starting Laravel application initialization..."

# Ensure storage directories exist and have correct permissions
echo "Setting up storage directories..."
mkdir -p storage/framework/{cache,data,sessions,views} \
    storage/app/public \
    storage/logs \
    bootstrap/cache

chmod -R 775 storage bootstrap/cache || true

# Check if APP_KEY is set, generate if missing
if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is not set. Generating application key..."
    php artisan key:generate --force || echo "Failed to generate APP_KEY - please set APP_KEY in environment variables"
else
    echo "APP_KEY is set."
fi

# Clear caches first
echo "Clearing existing caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Cache configuration and routes for better performance (only if APP_KEY is set)
if [ -n "$APP_KEY" ]; then
    echo "Optimizing Laravel for production..."
    php artisan config:cache || echo "Warning: Failed to cache config"
    php artisan route:cache || echo "Warning: Failed to cache routes"
    php artisan view:cache || echo "Warning: Failed to cache views"
else
    echo "Skipping cache optimization - APP_KEY not set"
fi

# Run database migrations (optional - uncomment if you want auto-migration)
# php artisan migrate --force || echo "Warning: Database migration failed"

# Display environment info
echo "Environment: ${APP_ENV:-production}"
echo "Debug mode: ${APP_DEBUG:-false}"
echo "Starting server on port: $PORT"

# Start the Laravel development server
exec php artisan serve --host=0.0.0.0 --port=$PORT

