#!/bin/bash
set -e

PORT="${PORT:-8080}"
echo "==> HRMS starting on port $PORT"

# Write .env from Railway environment variables
cat > /var/www/html/.env <<EOF
APP_NAME="${APP_NAME:-HRMS Enterprise}"
APP_URL="${APP_URL:-http://localhost}"
APP_DEBUG=${APP_DEBUG:-false}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-$(php -r "echo bin2hex(random_bytes(16));")}

DB_HOST=${MYSQLHOST:-${DB_HOST:-127.0.0.1}}
DB_PORT=${MYSQLPORT:-${DB_PORT:-3306}}
DB_DATABASE=${MYSQLDATABASE:-${DB_DATABASE:-hrms_db}}
DB_USERNAME=${MYSQLUSER:-${DB_USERNAME:-root}}
DB_PASSWORD=${MYSQLPASSWORD:-${DB_PASSWORD:-}}

MAIL_HOST=${MAIL_HOST:-smtp.gmail.com}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_FROM=${MAIL_FROM:-noreply@hrms.local}
MAIL_FROM_NAME="${MAIL_FROM_NAME:-HRMS Platform}"

SESSION_LIFETIME=${SESSION_LIFETIME:-1800}
SESSION_SECURE=${SESSION_SECURE:-false}
UPLOAD_MAX_SIZE=10485760
COMPANY_TIMEZONE=Asia/Karachi
EOF
echo "==> .env written"

# Import DB schema in background (Apache/PHP starts immediately)
(
  H="${MYSQLHOST:-${DB_HOST:-}}"
  P="${MYSQLPORT:-${DB_PORT:-3306}}"
  D="${MYSQLDATABASE:-${DB_DATABASE:-hrms_db}}"
  U="${MYSQLUSER:-${DB_USERNAME:-}}"
  W="${MYSQLPASSWORD:-${DB_PASSWORD:-}}"

# Wait for MySQL to be ready (Railway needs a moment)
echo "==> Waiting for MySQL..."
for i in $(seq 1 30); do
  if php -r "new PDO('mysql:host=$DB_HOST_VAL;port=$DB_PORT_VAL;dbname=$DB_NAME_VAL', '$DB_USER_VAL', '$DB_PASS_VAL');" 2>/dev/null; then
    echo "==> MySQL is ready!"
    break
  fi
  echo "    Attempt $i/30 — waiting 2s..."
  sleep 2
done

# Check if tables already exist
TABLE_COUNT=$(php -r "
try {
  \$pdo = new PDO('mysql:host=$DB_HOST_VAL;port=$DB_PORT_VAL;dbname=$DB_NAME_VAL', '$DB_USER_VAL', '$DB_PASS_VAL');
  echo \$pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\"$DB_NAME_VAL\"')->fetchColumn();
} catch(Exception \$e) { echo 0; }
" 2>/dev/null || echo 0)

if [ "$TABLE_COUNT" -lt "5" ]; then
  echo "==> Importing database schema..."
  mysql -h "$DB_HOST_VAL" -P "$DB_PORT_VAL" -u "$DB_USER_VAL" -p"$DB_PASS_VAL" "$DB_NAME_VAL" < /var/www/html/database/schema.sql && \
    echo "==> ✅ Schema imported successfully!" || \
    echo "==> ⚠️  Schema import failed — check logs"
else
  echo "==> Database already has $TABLE_COUNT tables, skipping import"
fi

echo "==> Configuring Apache to listen on port ${PORT:-80}..."
sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf

echo "==> Starting Apache..."
exec apache2-foreground
