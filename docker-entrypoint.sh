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

  [ -z "$H" ] && echo "==> [bg] No DB host set, skipping" && exit 0

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
          && echo "==> [bg] ✅ Done" \
          || echo "==> [bg] ⚠ Failed"
      else
        echo "==> [bg] Schema already present ($TABLES tables)"
      fi
      exit 0
    fi
    echo "==> [bg] Waiting for MySQL... ($i/40)"
    sleep 3
  done
) &

# Start PHP built-in server — listens directly on $PORT, zero config needed
echo "==> PHP server ready — http://0.0.0.0:${PORT}"
exec php -S 0.0.0.0:${PORT} -t /var/www/html/public /var/www/html/public/router.php
