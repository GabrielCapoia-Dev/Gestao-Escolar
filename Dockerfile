FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip nano libpng-dev libonig-dev libxml2-dev libzip-dev \
    libpq-dev libjpeg-dev libfreetype6-dev libicu-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip mbstring exif pcntl bcmath gd

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./

COPY . .

RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

COPY entrypoint.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 80 9000

CMD ["php-fpm"]