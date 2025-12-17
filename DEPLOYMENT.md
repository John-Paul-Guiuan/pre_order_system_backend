# Laravel Deployment Guide for Render

## Overview
This guide explains how to deploy your Laravel application on Render using Docker and troubleshoot common 500 errors.

## Common Causes of 500 Errors

### 1. Missing APP_KEY
**Problem**: Laravel requires `APP_KEY` to be set. Without it, encryption fails and causes 500 errors.

**Solution**: The `docker-entrypoint.sh` script automatically generates an `APP_KEY` if it's missing. However, for production, you should set it manually in Render's environment variables.

### 2. Database Connection Issues
**Problem**: If the database connection fails, Laravel may return a 500 error.

**Solution**: Ensure all PostgreSQL environment variables are correctly set in Render.

### 3. Storage Permissions
**Problem**: Laravel needs write access to `storage/` and `bootstrap/cache/` directories.

**Solution**: The Dockerfile and entrypoint script handle this automatically.

### 4. Missing Environment Variables
**Problem**: Required environment variables not set in Render.

**Solution**: See the "Required Environment Variables" section below.

## Required Environment Variables in Render

Set these in your Render service's Environment tab:

### Essential Variables
```
APP_NAME=YourAppName
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com
```

### Database Variables (PostgreSQL)
```
DB_CONNECTION=pgsql
DB_HOST=your-db-host.onrender.com
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
```

### Optional but Recommended
```
LOG_CHANNEL=stack
LOG_LEVEL=error
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

## Step-by-Step Deployment Process

### Step 1: Generate APP_KEY Locally
Before deploying, generate your APP_KEY:

```bash
php artisan key:generate --show
```

Copy the output (starts with `base64:`) and set it as `APP_KEY` in Render's environment variables.

### Step 2: Configure Render Service

1. **Create a new Web Service** in Render
2. **Connect your repository**
3. **Configure the service:**
   - **Name**: Your app name
   - **Environment**: Docker
   - **Dockerfile Path**: `Dockerfile` (or leave blank if in root)
   - **Docker Context**: `.` (root directory)

### Step 3: Set Environment Variables

In Render's Environment tab, add all required variables listed above.

**Important**: 
- Set `APP_URL` to your Render service URL (e.g., `https://your-app-name.onrender.com`)
- Ensure `APP_DEBUG=false` for production
- Set `APP_ENV=production`

### Step 4: Database Setup

1. **Create a PostgreSQL database** in Render
2. **Copy the database connection details** from Render
3. **Set the database environment variables** in your web service
4. **Run migrations** (you can do this manually via Render's shell or uncomment the migration line in `docker-entrypoint.sh`)

### Step 5: Deploy

1. Click **"Save Changes"** in Render
2. Render will build and deploy your application
3. Monitor the build logs for any errors

## Troubleshooting

### Check Application Logs

In Render, go to your service → **Logs** tab to see real-time logs.

### Common Issues and Solutions

#### Issue: Still getting 500 errors
**Solution**: 
1. Check Render logs for specific error messages
2. Verify `APP_KEY` is set correctly
3. Verify database connection variables are correct
4. Check that `APP_URL` matches your Render service URL

#### Issue: Database connection errors
**Solution**:
1. Verify PostgreSQL database is running
2. Check database credentials in environment variables
3. Ensure `DB_CONNECTION=pgsql` is set
4. Verify database host includes port if needed

#### Issue: Permission denied errors
**Solution**: The Dockerfile handles permissions automatically. If issues persist:
1. Check Render logs for specific permission errors
2. Verify storage directories exist (handled by entrypoint script)

#### Issue: Application key generation fails
**Solution**: 
1. Ensure `APP_KEY` is manually set in Render environment variables
2. The entrypoint script will try to generate one if missing, but manual setting is preferred

### Testing Database Connection

You can test your database connection by temporarily enabling debug mode:

1. Set `APP_DEBUG=true` in Render (temporarily)
2. Visit your application URL
3. Check for detailed error messages
4. **Remember to set `APP_DEBUG=false` again after debugging**

## What the Dockerfile Does

1. **Uses PHP 8.2 CLI** - Lightweight image for Laravel
2. **Installs required extensions** - PostgreSQL, GD, MBString, BCMath
3. **Installs Composer dependencies** - Optimized for production
4. **Sets up storage directories** - Creates required Laravel directories
5. **Sets permissions** - Ensures Laravel can write to storage and cache

## What the Entrypoint Script Does

1. **Checks for APP_KEY** - Generates one if missing (warning: not ideal for production)
2. **Clears caches** - Removes old cached config/routes
3. **Caches for production** - Optimizes config, routes, and views
4. **Handles port** - Uses Render's `PORT` environment variable
5. **Starts the server** - Runs `php artisan serve`

## Production Best Practices

1. **Always set APP_KEY manually** - Don't rely on auto-generation
2. **Use environment variables** - Never commit `.env` files
3. **Set APP_DEBUG=false** - Hide sensitive error information
4. **Use database migrations** - Run migrations via Render shell or CI/CD
5. **Monitor logs** - Regularly check Render logs for issues
6. **Use caching** - The entrypoint script enables config/route/view caching

## Running Migrations

You have two options:

### Option 1: Manual Migration (Recommended)
1. Go to your Render service
2. Click **"Shell"** tab
3. Run: `php artisan migrate --force`

### Option 2: Automatic Migration
Uncomment this line in `docker-entrypoint.sh`:
```bash
php artisan migrate --force || echo "Warning: Database migration failed"
```

**Note**: Automatic migrations can cause issues if migrations fail, so manual is preferred.

## Health Check

Your Laravel application includes a health check endpoint at `/up` (configured in `bootstrap/app.php`). Render can use this for health checks.

## Additional Notes

- The application uses port 8080 by default, but Render's `PORT` environment variable will override this
- Storage is ephemeral - files uploaded to `storage/app/public` will be lost on redeploy
- Consider using external storage (S3, etc.) for file uploads in production
- The `php artisan serve` command is suitable for Render, but for high-traffic applications, consider using PHP-FPM with Nginx (requires additional configuration)

