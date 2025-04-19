ARG PHP_VERSION=8.4.6-cli-alpine3.21
ARG COMPOSER_VERSION=2.8.8
ARG RR_VERSION=2024.3.4

FROM php:${PHP_VERSION} AS base

RUN apk add --no-cache \
        bash \
        curl \
        wget \
        git \
        unzip \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        libpq-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libxml2-dev

RUN  --mount=type=bind,from=mlocati/php-extension-installer:2.7.31,source=/usr/bin/install-php-extensions,target=/usr/local/bin/install-php-extensions \
     install-php-extensions \
     opcache \
     zip \
     xsl \
     dom \
     exif \
     intl \
     pcntl \
     bcmath \
     sockets \
     mbstring \
     pdo_mysql \
     mysqli \
     redis \
     opentelemetry \
     grpc  \
     protobuf

COPY --from=ghcr.io/roadrunner-server/roadrunner:2024.3.4 /usr/bin/rr /usr/bin/rr

COPY --from=composer/composer:2.8.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN addgroup -g 1000 -S appgroup \
    && adduser -u 1000 -S appuser -G appgroup \
    && chown appuser:appgroup /app

RUN apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/* /tmp/*

USER appuser

EXPOSE 8080/tcp

CMD ["php", "-v"]
