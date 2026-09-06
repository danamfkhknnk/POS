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

RUN mkdir -p /var/log/supervisor /var/cache/nginx

EXPOSE 80 6001

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
