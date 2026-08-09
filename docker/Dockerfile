FROM php:8.2-apache

# System libs for the PHP extensions OXID 7 needs
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip default-mysql-client \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libicu-dev libxml2-dev libzip-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mysqli gd soap bcmath intl zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# OXID 7 document root is the project's source/ dir; enable rewrites + .htaccess
ENV APACHE_DOCUMENT_ROOT=/var/www/html/source
RUN a2enmod rewrite \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!<Directory /var/www/>!<Directory /var/www/html/source/>!g' /etc/apache2/apache2.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

COPY php.ini /usr/local/etc/php/conf.d/zz-oxid.ini

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
