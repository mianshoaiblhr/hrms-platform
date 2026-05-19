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
    mysqladmin ping -h 127.0.0.1 --silent 2>/dev/null && echo "==> MariaDB ready after ${i}s" && break
    sleep 1
done

# ── 2. Create database and user ───────────────────────────────────────────
mysql -h 127.0.0.1 -u root 2>/dev/null << 'SQL'
CREATE DATABASE IF NOT EXISTS hrms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'hrms'@'127.0.0.1' IDENTIFIED BY 'hrms_secret';
GRANT ALL PRIVILEGES ON hrms_db.* TO 'hrms'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

# ── 3. Import schema + patches ────────────────────────────────────────────
TABLE_COUNT=$(mysql -h 127.0.0.1 -u hrms -phrms_secret hrms_db \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='hrms_db';" \
    --skip-column-names 2>/dev/null || echo "0")

if [ "${TABLE_COUNT:-0}" -lt 5 ]; then
    echo "==> Importing schema..."
    mysql -h 127.0.0.1 -u hrms -phrms_secret hrms_db \
        < /var/www/html/database/schema.sql 2>/dev/null && echo "==> Schema done"

    echo "==> Applying patches..."
    mysql -h 127.0.0.1 -u hrms -phrms_secret hrms_db \
        < /var/www/html/database/patches.sql 2>/dev/null && echo "==> Patches done"
fi

# ── 4. Admin user via PHP (avoids shell $ expansion destroying bcrypt hash) ──
echo "==> Setting up admin user..."
php -r "
try {
    \$pdo = new PDO('mysql:host=127.0.0.1;dbname=hrms_db;charset=utf8mb4', 'hrms', 'hrms_secret');
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get super_admin role id
    \$roleId = \$pdo->query(\"SELECT id FROM roles WHERE slug='super_admin' LIMIT 1\")->fetchColumn();
    if (!\$roleId) \$roleId = 1;

    // Generate fresh hash — PHP handles the special chars safely
    \$hash = password_hash('Admin@123', PASSWORD_BCRYPT, ['cost' => 10]);

    // Insert or update admin
    \$exists = \$pdo->prepare('SELECT id FROM users WHERE username = ?');
    \$exists->execute(['admin']);
    \$userId = \$exists->fetchColumn();

    if (\$userId) {
        \$upd = \$pdo->prepare('UPDATE users SET password=?, is_active=1, is_super_admin=1, role_id=? WHERE id=?');
        \$upd->execute([\$hash, \$roleId, \$userId]);
        echo 'Admin user updated' . PHP_EOL;
    } else {
        \$ins = \$pdo->prepare('INSERT INTO users (full_name,username,email,password,role_id,is_active,is_super_admin,created_at) VALUES (?,?,?,?,?,1,1,NOW())');
        \$ins->execute(['Super Admin','admin','admin@hrms.local',\$hash,\$roleId]);
        echo 'Admin user created' . PHP_EOL;
    }
    echo 'Login: admin / Admin@123' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Admin setup error: ' . \$e->getMessage() . PHP_EOL;
}
"

# ── 5. Write .env ─────────────────────────────────────────────────────────
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

# ── 6. Start PHP ──────────────────────────────────────────────────────────
echo "==> Ready on port ${PORT}"
exec php -S 0.0.0.0:${PORT} -t /var/www/html/public /var/www/html/public/router.php
