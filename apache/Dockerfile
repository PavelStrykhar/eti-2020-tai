FROM php:7.4-apache-buster
WORKDIR /var/www
RUN rm -rf /var/www/html && a2enmod rewrite && DEBIAN_FRONTEND=noninteractive apt-get -y update && apt-get -y install git zip curl 
RUN docker-php-ext-install pdo_mysql mysqli
RUN DEBIAN_FRONTEND=noninteractive apt-get -y update && apt-get -y install mariadb-client nano
COPY --from=composer /usr/bin/composer /usr/bin/composer
