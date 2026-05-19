#!/bin/bash
set -e

PORT="${PORT:-8080}"
echo "==> ORBIT HRMS starting on port $PORT"

# ── 1. Start MariaDB ──────────────────────────────────────────────────────
echo "==> Starting MariaDB..."
mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql 2>/dev/null || true

# Initialize data directory if needed
if [ ! -d "/var/lib/mysql/mysql" ]; then
    mysql_install_db --user=mysql --datadir=/var/lib/mysql > /dev/null 2>&1
fi

# Start MariaDB in background
mysqld_safe --user=mysql --skip-networking=0 &
MYSQL_PID=$!

# Wait for MariaDB to be ready
echo "==> Waiting for MariaDB..."
for i in $(seq 1 30); do
    if mysqladmin ping --silent 2>/dev/null; then
        echo "==> MariaDB ready"
        break
    fi
    sleep 1
done

# ── 2. Set up database ────────────────────────────────────────────────────
echo "==> Setting up database..."
mysql -u root 2>/dev/null << SQL
CREATE DATABASE IF NOT EXISTS hrms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'hrms'@'localhost' IDENTIFIED BY 'hrms_secret';
GRANT ALL PRIVILEGES ON hrms_db.* TO 'hrms'@'localhost';
FLUSH PRIVILEGES;
SQL

# Import schema
TABLE_COUNT=$(mysql -u hrms -phrms_secret hrms_db \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='hrms_db';" \
    --skip-column-names 2>/dev/null || echo "0")

if [ "${TABLE_COUNT:-0}" -lt 5 ]; then
    echo "==> Importing schema..."
    mysql -u hrms -phrms_secret hrms_db < /var/www/html/database/schema.sql 2>/dev/null \
        && echo "==> ✅ Schema imported" \
        || echo "==> ⚠ Schema import failed"
else
    echo "==> DB already has $TABLE_COUNT tables"
fi

# ── 3. Write .env ─────────────────────────────────────────────────────────
cat > /var/www/html/.env << ENV
APP_NAME=ORBIT HRMS
APP_URL=https://${RAILWAY_PUBLIC_DOMAIN:-localhost}
APP_ENV=production
APP_DEBUG=false
APP_KEY=$(php -r "echo bin2hex(random_bytes(16));")

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hrms_db
DB_USERNAME=hrms
DB_PASSWORD=hrms_secret

SESSION_LIFETIME=1800
SESSION_SECURE=false
UPLOAD_MAX_SIZE=10485760
COMPANY_TIMEZONE=Asia/Karachi
ENV
echo "==> .env written"

# ── 4. Start PHP server ───────────────────────────────────────────────────
echo "==> PHP server on port $PORT"
exec php -S 0.0.0.0:${PORT} -t /var/www/html/public /var/www/html/public/router.php
