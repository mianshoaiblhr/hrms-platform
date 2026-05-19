FROM php:8.2-apache

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libwebp-dev libzip-dev \
    libonig-dev libxml2-dev unzip git curl \
    && docker-php-ext-install pdo pdo_mysql mbstring \
       gd zip opcache exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers deflate expires

# PHP production settings
RUN echo "upload_max_filesize = 20M"    >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "post_max_size = 25M"          >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "memory_limit = 256M"          >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "max_execution_time = 120"     >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "opcache.enable = 1"           >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/hrms.ini \
 && echo "expose_php = Off"             >> /usr/local/etc/php/conf.d/hrms.ini

# Apache VirtualHost — point to /public
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes +FollowSymLinks\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Copy application files
WORKDIR /var/www/html
COPY . .

# Create writable storage directories
RUN mkdir -p storage/logs storage/uploads/employees \
             storage/uploads/documents storage/uploads/avatars \
             storage/cache storage/backups storage/temp \
 && chmod -R 777 storage/ \
 && chmod -R 755 public/ \
 && chown -R www-data:www-data /var/www/html

# Copy .env.example to .env if .env doesn't exist
RUN [ -f .env ] || cp .env.example .env

EXPOSE 80

# Startup: write .env from Railway env vars, then start Apache
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
CMD ["docker-entrypoint.sh"]
