FROM node:22-alpine AS frontend
WORKDIR /app

COPY package*.json vite.config.* ./
RUN npm install --no-audit --no-fund

COPY resources ./resources
COPY public ./public

# Automated FE Unit Tests
RUN npm run test:frontend
RUN npm run build

FROM php:8.5-cli-alpine

# Runtime tooling, C headers for PHP extensions, and PCOV for coverage.
# The build-only deps (.build) are dropped at the end of the same layer.
RUN apk add --no-cache sqlite-dev libzip-dev icu-dev unzip \
 && docker-php-ext-install pdo_sqlite zip intl \
 && apk add --no-cache --virtual .build autoconf build-base \
 && pecl install pcov \
 && docker-php-ext-enable pcov \
 && apk del .build

# Get the composer binary into the final image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-interaction --prefer-dist

# Copy source + built frontend, finalize autoload
COPY . .
COPY --from=frontend /app/public/build ./public/build
RUN rm -f public/hot \
 && composer dump-autoload --optimize \
 && mkdir -p storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
             storage/framework/testing \
             bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Automated BE Unit Tests
RUN vendor/bin/phpunit
EXPOSE 8080

# Generate APP_KEY and ensure SQLite volume file exists. If so; migrate, seed, and serve
CMD : "${APP_KEY:=base64:$(php -r 'echo base64_encode(random_bytes(32));')}" \
 && export APP_KEY \
 && mkdir -p /sqlite && touch /sqlite/database.sqlite \
 && php artisan migrate --force --graceful \
 && php artisan db:seed --force \
 && php artisan serve --host=0.0.0.0 --port=8080 2>&1 \
    | awk '{ gsub(/0\.0\.0\.0/, "127.0.0.1"); print; fflush() }'
# Small trick at the end to make sure end-user does not try to reach 0.0.0.0
