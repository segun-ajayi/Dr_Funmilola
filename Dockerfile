FROM node:24-alpine AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.ts tsconfig.json ./
COPY public ./public
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --classmap-authoritative

FROM php:8.5-fpm-alpine AS runtime
RUN apk add --no-cache icu-libs libzip postgresql-libs fcgi \
 && apk add --no-cache --virtual .build-deps icu-dev libzip-dev postgresql-dev $PHPIZE_DEPS \
 && docker-php-ext-install -j$(nproc) intl opcache pcntl pdo_pgsql zip \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del .build-deps
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /build/vendor ./vendor
COPY --from=frontend /build/public/build ./public/build
COPY deployment/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD SCRIPT_NAME=/fpm-ping SCRIPT_FILENAME=/fpm-ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1
CMD ["php-fpm","-F"]

FROM nginx:1.29-alpine AS web
COPY public /var/www/html/public
COPY --from=frontend /build/public/build /var/www/html/public/build
COPY deployment/nginx/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 8080
