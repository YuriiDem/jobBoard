FROM node:22-alpine AS assets
WORKDIR /src
COPY package*.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources resources
RUN npm run build

FROM alpine:3.22 AS runtime
RUN apk add --no-cache php84 php84-fpm php84-opcache php84-bcmath php84-ctype php84-curl php84-dom php84-fileinfo php84-gd php84-intl php84-mbstring php84-openssl php84-pcntl php84-pdo_pgsql php84-pecl-redis php84-session php84-simplexml php84-sodium php84-tokenizer php84-xml php84-xmlreader php84-xmlwriter php84-zip php84-phar php84-iconv php84-posix php84-exif unzip \
 && ln -sf /usr/bin/php84 /usr/local/bin/php \
 && addgroup -g 10001 board && adduser -D -u 10001 -G board board
WORKDIR /srv/board
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --chown=board:board . .
COPY --from=assets /src/public/build public/build
RUN mkdir -p vendor storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache state \
 && chown -R board:board vendor storage bootstrap/cache state \
 && sed -i 's/^listen = .*/listen = 9000/;s/^user = .*/user = board/;s/^group = .*/group = board/' /etc/php84/php-fpm.d/www.conf \
 && echo 'clear_env = no' >> /etc/php84/php-fpm.d/www.conf
RUN sed -i 's|^;*error_log = .*|error_log = /proc/self/fd/2|' /etc/php84/php-fpm.conf
USER board
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
ENTRYPOINT ["sh","docker/start.sh"]
CMD ["php-fpm84","-F"]

FROM nginx:stable-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=runtime /srv/board/public /srv/board/public
