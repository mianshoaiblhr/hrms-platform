#!/bin/bash
set -e

PORT="${PORT:-8080}"
echo "==> ORBIT HRMS starting on port $PORT"

# ── 1. Start MariaDB ──────────────────────────────────────────────────────
echo "==> Starting MariaDB..."
mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql 2>/dev/null || true

if [ ! -d "/var/lib/mysql/mysql" ]; then
    mysql_install_db --datadir=/var/lib/mysql --user=mysql > /dev/null 2>&1
fi

mysqld_safe --skip-networking=0 --bind-address=127.0.0.1 &

echo "==> Waiting for MariaDB..."
for i in $(seq 1 40); do
    mysqladmin ping -h 127.0.0.1 --silent 2>/dev/null && echo "==> MariaDB ready" && break
    sleep 1
done

# ── 2. Create DB and import schema ────────────────────────────────────────
mysql -h 127.0.0.1 -u root 2>/dev/null << SQL
CREATE DATABASE IF NOT EXISTS hrms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'hrms'@'127.0.0.1' IDENTIFIED BY 'hrms_secret';
GRANT ALL PRIVILEGES ON hrms_db.* TO 'hrms'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

TABLE_COUNT=$(mysql -h 127.0.0.1 -u hrms -phrms_secret hrms_db     -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='hrms_db';"     --skip-column-names 2>/dev/null || echo "0")

if [ "${TABLE_COUNT:-0}" -lt 5 ]; then
    echo "==> Importing schema..."
    mysql -h 127.0.0.1 -u hrms -phrms_secret hrms_db         < /var/www/html/database/schema.sql 2>/dev/null         && echo "==> Schema imported" || echo "==> Schema import warning (may have partial errors)"
fi

# ── 3. Ensure admin user exists with correct password ─────────────────────
echo "==> Ensuring admin user..."
mysql -h 127.0.0.1 -u hrms -phrms_secret hrms_db 2>/dev/null << SQL
INSERT INTO users (name, username, email, password, role_id, is_active, is_super_admin, created_at)
VALUES ('Super Admin','admin','admin@hrms.local','$2y$12$VzzHeIylwA2glATaVD4MwuiDiVL9itqxMI/nOQfAndZP0svg2YzwS',1,1,1,NOW())
ON DUPLICATE KEY UPDATE
    password='$2y$12$VzzHeIylwA2glATaVD4MwuiDiVL9itqxMI/nOQfAndZP0svg2YzwS',
    is_active=1,
    is_super_admin=1;
SQL
echo "==> Admin user ready (admin / Admin@123)"

# ── 4. Write .env ─────────────────────────────────────────────────────────
cat > /var/www/html/.env << ENV
APP_NAME=ORBIT HRMS
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
COMPANY_TIMEZONE=Asia/Karachi
ENV
echo "==> .env written"

# ── 5. Start PHP ──────────────────────────────────────────────────────────
echo "==> Ready — http://0.0.0.0:${PORT}"
exec php -S 0.0.0.0:${PORT} -t /var/www/html/public /var/www/html/public/router.php
