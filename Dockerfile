FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    ghostscript \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip \
    && apt-get clean

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads /var/www/html/compressed \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/compressed \
    && chmod -R 775 /var/www/html/uploads /var/www/html/compressed

EXPOSE 80