FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip unzip \
    && docker-php-ext-install pdo pdo_mysql mbstring

RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . .

RUN echo '<Directory /var/www/html>\nAllowOverride All\nRequire all granted\n</Directory>' > /etc/apache2/conf-available/custom.conf \
    && a2enconf custom

RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

EXPOSE 80
CMD ["apache2-foreground"]