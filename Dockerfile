# Étape 1 : Vite compile uniquement les feuilles de style et scripts publics.
FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./vite.config.js
RUN npm run build

# Étape 2 : Composer installe les dépendances PHP sans les outils de développement.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction

# Étape 3 : Apache sert Laravel depuis public/ avec les extensions réellement utilisées.
FROM php:8.4-apache-bookworm AS runtime
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev libfreetype6-dev libicu-dev libjpeg62-turbo-dev libonig-dev libpng-dev libpq-dev libxml2-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" curl dom gd intl mbstring opcache pdo_pgsql zip \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

# Apache doit pointer vers le seul dossier publiquement accessible de Laravel.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf 'ServerName 0.0.0.0\n' > /etc/apache2/conf-available/abiwein-server-name.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/abiwein-public.conf \
    && a2enconf abiwein-server-name abiwein-public

# Le code, les dépendances PHP et les ressources compilées sont copiés sans secrets locaux.
WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/abiwein.ini
COPY docker/start.sh /usr/local/bin/abiwein-start
RUN chmod +x /usr/local/bin/abiwein-start \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000
CMD ["abiwein-start"]
