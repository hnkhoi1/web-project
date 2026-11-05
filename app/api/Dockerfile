# syntax=docker/dockerfile:1

FROM php:8.5-fpm-trixie AS base
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl gd \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html

FROM base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

FROM base AS assets
COPY --from=node:26-trixie-slim /usr/local /usr/local
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN npm install --global pnpm@12
COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
# The project .env is outside the build context, so Vite needs the name as a build arg.
ARG VITE_APP_NAME="Tra cứu Luật Hình sự"
ENV VITE_APP_NAME=$VITE_APP_NAME
ENV APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
RUN composer dump-autoload --no-dev --no-scripts --optimize \
    && php artisan package:discover --ansi \
    && pnpm install --frozen-lockfile \
    && pnpm build

FROM base AS dev
ARG UID=1000
ENV npm_config_store_dir=/home/dev/.pnpm-store
COPY --from=node:26-trixie-slim /usr/local /usr/local
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN npm install --global pnpm@12 \
    && useradd -m -u ${UID} dev \
    && mkdir -p /run/app-key /var/www/html/vendor /var/www/html/node_modules \
    && chown -R dev /run/app-key /var/www/html /home/dev
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
USER dev
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

FROM base AS prod
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
COPY --from=assets /var/www/html/public/build ./public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p /run/app-key storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && rm /usr/bin/composer
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

FROM nginx:stable-trixie AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
COPY --from=assets /var/www/html/public/build /var/www/html/public/build
