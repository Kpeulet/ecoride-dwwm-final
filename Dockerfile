FROM php:8.2-apache

# Installation des dépendances système pour MongoDB
RUN apt-get update && apt-get install -y libssl-dev

# Extensions MySQL et MongoDB
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Activation du module rewrite Apache
RUN a2enmod rewrite

# Copie des fichiers du projet
COPY . /var/www/html/

EXPOSE 80