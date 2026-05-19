#!/bin/bash
set -e

echo "==> HRMS starting..."

# ── 1. Resolve PORT ───────────────────────────────────────────────────────────
# Railway injects $PORT (usually 8080). Default to 80 if not set.
APP_PORT="${PORT:-80}"
echo "==> Port: $APP_PORT"

# Write a clean ports.conf for this port
echo "Listen ${APP_PORT}" > /etc/apache2/ports.conf

# Inject the port into the VirtualHost config
sed -i "s/__PORT__/${APP_PORT}/g" /etc/apache2/sites-available/000-default.conf

# ── 2. Write .env ─────────────────────────────────────────────────────────────
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

# ── 3. Background DB schema import ───────────────────────────────────────────
(
  H="${MYSQLHOST:-${DB_HOST:-}}"
  P="${MYSQLPORT:-${DB_PORT:-3306}}"
  D="${MYSQLDATABASE:-${DB_DATABASE:-}}"
  U="${MYSQLUSER:-${DB_USERNAME:-}}"
  W="${MYSQLPASSWORD:-${DB_PASSWORD:-}}"

  [ -z "$H" ] && echo "==> [bg] No DB host — skipping import" && exit 0

  echo "==> [bg] Waiting for MySQL at $H:$P ..."
  for i in $(seq 1 40); do
    if mysqladmin ping -h "$H" -P "$P" -u "$U" -p"$W" --silent 2>/dev/null; then
      echo "==> [bg] MySQL ready"
      TABLES=$(mysql -h "$H" -P "$P" -u "$U" -p"$W" \
        -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$D';" \
        --skip-column-names 2>/dev/null || echo "0")
      if [ "${TABLES:-0}" -lt 5 ]; then
        echo "==> [bg] Importing schema..."
        mysql -h "$H" -P "$P" -u "$U" -p"$W" "$D" \
          < /var/www/html/database/schema.sql 2>/dev/null \
          && echo "==> [bg] ✅ Schema imported" \
          || echo "==> [bg] ⚠ Import failed"
      else
        echo "==> [bg] DB already has $TABLES tables — skipping"
      fi
      exit 0
    fi
    sleep 3
  done
  echo "==> [bg] MySQL not reachable after 40 attempts"
) &

# ── 4. Start Apache (immediately, before DB is ready) ─────────────────────────
echo "==> Apache starting on port ${APP_PORT}..."
exec apache2-foreground
