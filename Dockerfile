FROM php:8.2-apache

# Install PHP extensions + mysql client
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libwebp-dev libzip-dev \
    libonig-dev libxml2-dev unzip git curl \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring \
       gd zip opcache exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Fix MPM conflict — forcefully delete ALL mpm_* symlinks from mods-enabled,
# then manually create only the mpm_prefork symlinks.
# a2dismod is a no-op here because Apache isn't running during build.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_worker.conf \
          /etc/apache2/mods-enabled/mpm_worker.load \
          /etc/apache2/mods-enabled/mpm_prefork.conf \
          /etc/apache2/mods-enabled/mpm_prefork.load \
 && ln -s /etc/apache2/mods-available/mpm_prefork.conf \
          /etc/apache2/mods-enabled/mpm_prefork.conf \
 && ln -s /etc/apache2/mods-available/mpm_prefork.load \
          /etc/apache2/mods-enabled/mpm_prefork.load

# Enable required modules
RUN a2enmod rewrite headers deflate expires

# PHP production settings
RUN { \
    echo "upload_max_filesize = 20M"; \
    echo "post_max_size = 25M"; \
    echo "memory_limit = 256M"; \
    echo "max_execution_time = 120"; \
    echo "opcache.enable = 1"; \
    echo "opcache.memory_consumption = 128"; \
    echo "expose_php = Off"; \
} >> /usr/local/etc/php/conf.d/hrms.ini

# Apache VirtualHost — HRMS_PORT replaced at runtime by entrypoint
RUN printf '<VirtualHost *:HRMS_PORT>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes +FollowSymLinks\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

# Copy application files
WORKDIR /var/www/html
COPY . .

# Writable storage directories
RUN mkdir -p storage/logs storage/uploads/employees \
             storage/uploads/documents storage/uploads/avatars \
             storage/cache storage/backups storage/temp \
 && chmod -R 777 storage/ \
 && chmod -R 755 public/ \
 && chown -R www-data:www-data /var/www/html

RUN [ -f .env ] || cp .env.example .env

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
CMD ["docker-entrypoint.sh"]
