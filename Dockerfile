FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    libzip-dev \
    zlib-dev \
    icu-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Install dependencies
RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist

# Change ownership to www-data
RUN chown -R www-data:www-data /var/www/html

USER www-data
