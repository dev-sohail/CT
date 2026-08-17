FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libzip-dev \
        libpq-dev \
        libpng-dev \
    && docker-php-ext-install pdo_mysql bcmath zip opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts || true

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction || true \
    && php artisan config:cache || true \
    && php artisan route:cache || true \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
