#!/bin/bash
set -e

echo "==> HRMS Enterprise — Starting up..."

# ── 1. Resolve PORT (Railway injects $PORT, default to 80) ──────────────────
PORT="${PORT:-80}"
echo "==> Configuring Apache on port $PORT"

# Update ports.conf
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/Listen 443/Listen 443/g" /etc/apache2/ports.conf 2>/dev/null || true

# Update VirtualHost port placeholder set at build time
sed -i "s/HRMS_PORT/$PORT/g" /etc/apache2/sites-available/000-default.conf

# ── 2. Write .env from Railway environment variables ────────────────────────
cat > /var/www/html/.env <<EOF
APP_NAME="${APP_NAME:-HRMS Enterprise}"
APP_URL="${APP_URL:-http://localhost}"
APP_DEBUG=${APP_DEBUG:-false}
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY:-$(php -r "echo bin2hex(random_bytes(16));")}

DB_HOST=${MYSQLHOST:-${RAILWAY_MYSQL_HOST:-${DB_HOST:-127.0.0.1}}}
DB_PORT=${MYSQLPORT:-${RAILWAY_MYSQL_PORT:-${DB_PORT:-3306}}}
DB_DATABASE=${MYSQLDATABASE:-${RAILWAY_MYSQL_DATABASE:-${DB_DATABASE:-hrms_db}}}
DB_USERNAME=${MYSQLUSER:-${RAILWAY_MYSQL_USER:-${DB_USERNAME:-root}}}
DB_PASSWORD=${MYSQLPASSWORD:-${RAILWAY_MYSQL_PASSWORD:-${DB_PASSWORD:-}}}

MAIL_HOST=${MAIL_HOST:-smtp.gmail.com}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USER=${MAIL_USER:-}
MAIL_PASS=${MAIL_PASS:-}
MAIL_FROM=${MAIL_FROM:-noreply@hrms.local}
MAIL_FROM_NAME="${MAIL_FROM_NAME:-HRMS Platform}"
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}

SESSION_LIFETIME=${SESSION_LIFETIME:-1800}
SESSION_SECURE=${SESSION_SECURE:-false}
UPLOAD_MAX_SIZE=10485760
COMPANY_TIMEZONE=Asia/Karachi
EOF
echo "==> .env written"

# ── 3. Import DB schema in background (does not block Apache startup) ────────
(
  DB_HOST="${MYSQLHOST:-${RAILWAY_MYSQL_HOST:-${DB_HOST:-127.0.0.1}}}"
  DB_PORT="${MYSQLPORT:-${RAILWAY_MYSQL_PORT:-${DB_PORT:-3306}}}"
  DB_NAME="${MYSQLDATABASE:-${RAILWAY_MYSQL_DATABASE:-${DB_DATABASE:-hrms_db}}}"
  DB_USER="${MYSQLUSER:-${RAILWAY_MYSQL_USER:-${DB_USERNAME:-root}}}"
  DB_PASS="${MYSQLPASSWORD:-${RAILWAY_MYSQL_PASSWORD:-${DB_PASSWORD:-}}}"

  echo "==> [bg] Waiting for MySQL at $DB_HOST:$DB_PORT..."
  for i in $(seq 1 40); do
    if mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; then
      echo "==> [bg] MySQL ready after ${i} attempts"
      break
    fi
    sleep 3
  done

  # Check if schema already imported
  TABLE_COUNT=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" \
    --skip-column-names 2>/dev/null || echo "0")

  if [ "${TABLE_COUNT:-0}" -lt 5 ]; then
    echo "==> [bg] Importing schema ($TABLE_COUNT tables found)..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
      < /var/www/html/database/schema.sql 2>/dev/null \
      && echo "==> [bg] ✅ Schema imported — $(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
           -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" \
           --skip-column-names 2>/dev/null) tables created" \
      || echo "==> [bg] ⚠ Schema import failed — check DB credentials"
  else
    echo "==> [bg] DB already has $TABLE_COUNT tables, skipping import"
  fi
) &

# ── 4. Start Apache in foreground (healthcheck can hit /health.php immediately) ──
echo "==> Starting Apache on port $PORT..."
exec apache2-foreground
