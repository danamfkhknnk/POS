FROM php:8.4-fpm-alpine AS builder

RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev zip unzip git nodejs npm

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

COPY . .
RUN npm ci && npm run build

FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev nginx supervisor curl

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd opcache

WORKDIR /var/www/html

COPY --from=builder /var/www/html /var/www/html

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
RUN rm -f /etc/nginx/http.d/default.conf.bak 2>/dev/null

COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

RUN mkdir -p /var/log/supervisor /var/cache/nginx \
    && mkdir -p /var/www/html/storage/framework/{sessions,views,cache} \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/app/public \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80 6001

ENTRYPOINT ["/docker-entrypoint.sh"]
