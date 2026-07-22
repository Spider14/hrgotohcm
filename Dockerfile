FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    unzip \
    ghostscript \
    && docker-php-ext-install zip pdo_mysql opcache \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP for Cloud Run
RUN set -ex; \
  { \
    echo "variables_order = EGPCS"; \
    echo "memory_limit = -1"; \
    echo "max_execution_time = 0"; \
    echo "upload_max_filesize = 32M"; \
    echo "post_max_size = 32M"; \
    echo "opcache.enable = On"; \
    echo "opcache.validate_timestamps = Off"; \
    echo "opcache.memory_consumption = 32"; \
  } > "$PHP_INI_DIR/conf.d/cloud-run.ini"

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install dependencies first (Docker layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copy application code
COPY . .

# Set permissions for writable directories
RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/public/uploads \
    && mkdir -p /var/www/html/app/storage/backups \
    && chmod -R 775 /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/app/storage

# Configure Apache to use Cloud Run's PORT environment variable
RUN sed -i 's/80/${PORT}/g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/ports.conf

EXPOSE ${PORT}
