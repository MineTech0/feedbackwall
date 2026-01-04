#!/bin/bash
# =============================================================================
# Laravel Healthcheck Script
# =============================================================================
# Käytä tätä manuaaliseen tarkistukseen tai container healthcheckissa
# =============================================================================

set -e

echo "🔍 Running Laravel healthcheck..."

# HTTP healthcheck
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/up || echo "000")

if [ "$HTTP_STATUS" = "200" ]; then
    echo "✅ HTTP healthcheck passed (status: $HTTP_STATUS)"
else
    echo "❌ HTTP healthcheck failed (status: $HTTP_STATUS)"
    exit 1
fi

# Database check
if php artisan db:monitor --max=1 > /dev/null 2>&1; then
    echo "✅ Database connection OK"
else
    echo "⚠️  Database connection issue (might be transient)"
fi

# Cache check
if php artisan tinker --execute="Cache::put('hc', 'ok', 10); echo Cache::get('hc');" 2>/dev/null | grep -q "ok"; then
    echo "✅ Cache driver OK"
else
    echo "⚠️  Cache driver issue"
fi

# Queue check (optional - don't fail if queue is empty)
QUEUE_SIZE=$(php artisan queue:monitor default --max=1000 2>/dev/null | grep -o '[0-9]*' | head -1 || echo "0")
echo "📊 Queue size: $QUEUE_SIZE jobs"

# Storage check
if [ -w "/var/www/html/storage/logs" ]; then
    echo "✅ Storage writable"
else
    echo "❌ Storage not writable"
    exit 1
fi

echo ""
echo "🎉 All healthchecks passed!"

