FROM php:8.2-cli

# Install PHP extensions and mysql client for schema imports
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libwebp-dev libzip-dev \
    libonig-dev libxml2-dev unzip git curl \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring \
       gd zip opcache exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Fix MPM conflict and enable Apache modules
RUN a2dismod mpm_event 2>/dev/null; a2enmod mpm_prefork rewrite headers deflate expires

# PHP production settings
RUN echo "upload_max_filesize = 20M"    >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "post_max_size = 25M"          >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "memory_limit = 256M"          >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "max_execution_time = 120"     >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "opcache.enable = 1"           >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "expose_php = Off"             >> /usr/local/etc/php/conf.d/hrms.ini

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
