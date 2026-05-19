FROM php:8.2-cli

# Install extensions + mysql client
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libwebp-dev libzip-dev \
    libonig-dev libxml2-dev unzip curl \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring gd zip opcache exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP settings
RUN { \
    echo "upload_max_filesize = 20M"; \
    echo "post_max_size = 25M"; \
    echo "memory_limit = 256M"; \
    echo "max_execution_time = 120"; \
    echo "opcache.enable = 0"; \
    echo "expose_php = Off"; \
} >> /usr/local/etc/php/conf.d/hrms.ini

WORKDIR /var/www/html
COPY . .

RUN mkdir -p storage/logs storage/uploads/employees \
             storage/uploads/documents storage/uploads/avatars \
             storage/cache storage/backups storage/temp \
 && chmod -R 777 storage/ \
 && chmod -R 755 public/

RUN [ -f .env ] || cp .env.example .env

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["docker-entrypoint.sh"]
