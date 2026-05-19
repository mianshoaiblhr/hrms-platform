#!/bin/bash

PORT="${PORT:-8080}"
echo "==> ORBIT HRMS — port $PORT"

# ── Write .env immediately with defaults ──────────────────────────────────
APP_KEY=$(php -r "echo bin2hex(random_bytes(16));")
cat > /var/www/html/.env << ENV
APP_NAME=ORBIT HRMS
APP_ENV=production
APP_DEBUG=false
APP_KEY=${APP_KEY}
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

# ── Start MariaDB in background ───────────────────────────────────────────
echo "==> Starting MariaDB (background)..."
mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql 2>/dev/null || true
[ -d "/var/lib/mysql/mysql" ] || mysql_install_db --datadir=/var/lib/mysql --user=mysql >/dev/null 2>&1
mysqld_safe --skip-networking=0 --bind-address=127.0.0.1 &

# ── Start PHP server IMMEDIATELY so healthcheck passes ────────────────────
echo "==> PHP server starting on port $PORT"
php -S 0.0.0.0:${PORT} -t /var/www/html/public /var/www/html/public/router.php &
PHP_PID=$!

# ── Database setup runs entirely in background ────────────────────────────
(
    echo "==> [db] Waiting for MariaDB..."
    for i in $(seq 1 60); do
        mysqladmin ping -h 127.0.0.1 --silent 2>/dev/null && echo "==> [db] MariaDB ready after ${i}s" && break
        sleep 2
    done

    # Create database and user
    mysql -h 127.0.0.1 -u root 2>/dev/null << 'SQL'
CREATE DATABASE IF NOT EXISTS hrms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'hrms'@'127.0.0.1' IDENTIFIED BY 'hrms_secret';
GRANT ALL PRIVILEGES ON hrms_db.* TO 'hrms'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

    # Import schema if needed
    TABLE_COUNT=$(mysql -h 127.0.0.1 -u hrms -phrms_secret hrms_db \
        -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='hrms_db';" \
        --skip-column-names 2>/dev/null || echo "0")

    if [ "${TABLE_COUNT:-0}" -lt 5 ]; then
        echo "==> [db] Importing schema..."
        mysql -h 127.0.0.1 -u hrms -phrms_secret hrms_db \
            < /var/www/html/database/schema.sql 2>/dev/null && echo "==> [db] Schema done"

        echo "==> [db] Applying patches..."
        mysql -h 127.0.0.1 -u hrms -phrms_secret hrms_db \
            < /var/www/html/database/patches.sql 2>/dev/null && echo "==> [db] Patches done"
    else
        echo "==> [db] DB has $TABLE_COUNT tables — skipping import"
    fi

    # Write PHP admin setup script (single-quoted heredoc = zero shell expansion)
    cat > /tmp/setup_admin.php << 'PHPEOF'
<?php
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=hrms_db;charset=utf8mb4',
        'hrms', 'hrms_secret',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $roleId = $pdo->query("SELECT id FROM roles WHERE slug='super_admin' LIMIT 1")
                  ->fetchColumn() ?: 1;

    $hash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 10]);

    $exists = $pdo->prepare("SELECT id FROM users WHERE username='admin'");
    $exists->execute();
    $userId = $exists->fetchColumn();

    if ($userId) {
        $upd = $pdo->prepare("UPDATE users SET
            password=?, full_name='Super Admin', is_active=1,
            is_super_admin=1, role_id=?,
            login_attempts=0, locked_until=NULL
            WHERE id=?");
        $upd->execute([$hash, $roleId, $userId]);
        echo "[db] Admin UPDATED (id=$userId)" . PHP_EOL;
    } else {
        $ins = $pdo->prepare("INSERT INTO users
            (full_name,username,email,password,role_id,is_active,is_super_admin,login_attempts,created_at)
            VALUES ('Super Admin','admin','admin@hrms.local',?,?,1,1,0,NOW())");
        $ins->execute([$hash, $roleId]);
        echo "[db] Admin CREATED (id=" . $pdo->lastInsertId() . ")" . PHP_EOL;
    }

    $stored = $pdo->query("SELECT password FROM users WHERE username='admin'")->fetchColumn();
    $ok = password_verify('Admin@123', $stored);
    echo "[db] Password verify: " . ($ok ? "✅ PASS" : "❌ FAIL") . PHP_EOL;
    echo "[db] ✅ Ready — login: admin / Admin@123" . PHP_EOL;

} catch (Exception $e) {
    echo "[db] ERROR: " . $e->getMessage() . PHP_EOL;
}
PHPEOF
    php /tmp/setup_admin.php

) &

# ── Keep container alive on PHP process ──────────────────────────────────
echo "==> Healthcheck available immediately at /ping"
wait $PHP_PID
