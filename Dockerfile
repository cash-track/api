FROM php:8.2.4-alpine3.17 as backend

RUN  --mount=type=bind,from=mlocati/php-extension-installer:1.5,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
      install-php-extensions opcache zip xsl dom exif intl pcntl bcmath sockets mbstring pdo_mysql mysqli && \
     apk del --no-cache  ${PHPIZE_DEPS} ${BUILD_DEPENDS}

COPY --from=ghcr.io/roadrunner-server/roadrunner:2.12.3 /usr/bin/rr /usr/bin/rr

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json /app
COPY composer.lock /app

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --ignore-platform-reqs --optimize-autoloader --no-dev --no-scripts

COPY . /app

EXPOSE 8080/tcp

ENTRYPOINT [ "rr", "serve", "-c", "/app/.rr.yaml" ]
