
# Use Node.js from the official Node image
FROM node:22-bookworm-slim AS node

# PHP + Apache production image
FROM php:8.4-apache

# Copy Node.js and npm into PHP image
COPY --from=node /usr/local/ /usr/local/

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    bcmath \
    exif \
    pcntl \
    zip \
    intl \
    opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy Laravel application
COPY . .

# Install PHP production dependencies first
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader

# Install frontend dependencies
RUN npm ci

# Build frontend assets (Wayfinder can now execute PHP Artisan)
RUN npm run build

# Set Laravel directory permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# Render uses its assigned PORT
EXPOSE 10000

CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -i \"s/*:80>/*:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"]
