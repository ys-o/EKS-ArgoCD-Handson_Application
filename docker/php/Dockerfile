FROM php:8.4-fpm-alpine
RUN docker-php-ext-install pdo_mysql
WORKDIR /var/www/app
COPY app/ ./
COPY docker/php/app.ini /usr/local/etc/php/conf.d/app.ini
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
