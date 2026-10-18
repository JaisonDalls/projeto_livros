FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev unzip \
    && docker-php-ext-install pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --prefer-dist --no-progress \
    && php bin/console asset-map:compile \
    && chown -R www-data:www-data var

EXPOSE 80
