FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory
COPY . .

# Install dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html

RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini
RUN echo "display_startup_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini
RUN echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

# Expose port 9000
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"] 