FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache bcmath gd

RUN pecl install redis && docker-php-ext-enable redis

# Configurações PHP-FPM (apenas configurações do FPM)
RUN { \
  echo "pm = static"; \
  echo "pm.max_children = 8"; \
} > /usr/local/etc/php-fpm.d/zz-performance.conf

# Configurações do PHP/Opcache (separado, em arquivo .ini)
RUN { \
  echo "opcache.enable=1"; \
  echo "opcache.enable_cli=0"; \
  echo "opcache.memory_consumption=128"; \
  echo "opcache.interned_strings_buffer=16"; \
  echo "opcache.max_accelerated_files=20000"; \
  echo "opcache.validate_timestamps=1"; \
} > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Criar diretórios necessários
RUN mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data /var/www

CMD ["php-fpm"]