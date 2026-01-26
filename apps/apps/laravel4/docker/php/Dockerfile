FROM php:7.4-cli

# system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libmcrypt-dev \
 && pecl install mcrypt-1.0.6 \
 && docker-php-ext-enable mcrypt \
 && docker-php-ext-install mbstring pdo pdo_mysql

# Composer (compatible with PHP 7.4)
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html