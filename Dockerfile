FROM php:8.2-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer install

#COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
#RUN chmod +x /usr/local/bin/entrypoint.sh
#ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

EXPOSE 8080


CMD ["php", "-S", "0.0.0.0:8080"]
