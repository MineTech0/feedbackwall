#!/bin/bash
# =============================================================================
# Laravel Production Entrypoint
# =============================================================================
# Tämä skripti ajetaan containerin käynnistyksessä.
# ServersideUp image käyttää S6 overlay:ta, joka ajaa /etc/entrypoint.d/ skriptit.
#
# CONTAINER_ROLE:
#   - app (default): Täysi alustus (migraatiot, cache, jne.)
#   - queue: Vain odota tietokantaa
#   - scheduler: Vain odota tietokantaa
# =============================================================================

set -e

ROLE=${CONTAINER_ROLE:-app}
echo "🚀 Laravel entrypoint starting (role: $ROLE)..."

# -----------------------------------------------------------------------------
# Wait for database (kaikille rooleille)
# -----------------------------------------------------------------------------
wait_for_database() {
    echo "⏳ Waiting for database connection..."
    
    MAX_ATTEMPTS=30
    ATTEMPT=0
    
    while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
        if php artisan db:monitor --max=1 2>/dev/null; then
            echo "✅ Database is ready!"
            return 0
        fi
        
        ATTEMPT=$((ATTEMPT + 1))
        echo "   Attempt $ATTEMPT/$MAX_ATTEMPTS - Database not ready, waiting..."
        sleep 2
    done
    
    echo "❌ Database connection failed after $MAX_ATTEMPTS attempts"
    return 1
}

# -----------------------------------------------------------------------------
# Queue/Scheduler role: vain odota tietokantaa
# -----------------------------------------------------------------------------
if [ "$ROLE" = "queue" ] || [ "$ROLE" = "scheduler" ]; then
    wait_for_database || exit 1
    echo "🎉 $ROLE container ready!"
    exit 0
fi

# -----------------------------------------------------------------------------
# App role: täysi alustus
# -----------------------------------------------------------------------------
wait_for_database || exit 1

# Run migrations (if enabled)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "📦 Running database migrations..."
    php artisan migrate --force
    echo "✅ Migrations completed!"
else
    echo "⏭️  Skipping migrations (RUN_MIGRATIONS != true)"
fi

# Cache configuration
echo "🗄️  Caching configuration..."

# Config cache (tärkeä: pitää ajaa AINA deploy yhteydessä)
php artisan config:cache

# Route cache
php artisan route:cache

# View cache
php artisan view:cache

# Event cache
php artisan event:cache

echo "✅ Configuration cached!"

# Storage link
echo "🔗 Creating storage link..."
php artisan storage:link 2>/dev/null || echo "   Storage link already exists"

# Telescope (optional)
if [ "$TELESCOPE_ENABLED" = "true" ]; then
    echo "🔭 Publishing Telescope assets..."
    php artisan telescope:publish --force 2>/dev/null || true
fi

# Done
echo "🎉 Laravel entrypoint completed!"
echo "   Environment: $APP_ENV"
echo "   Debug: $APP_DEBUG"
echo "   URL: $APP_URL"
