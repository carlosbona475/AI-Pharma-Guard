FROM php:8.2-apache

# MySQL (Hostinger / local); PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

COPY . /var/www/html/

EXPOSE 80
