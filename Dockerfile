FROM node:20-alpine3.18 AS mjml

WORKDIR /templates

COPY ./app/views/email-templates .

RUN npm install -g mjml &&  \
    mkdir out &&  \
    ./build.sh ./out


FROM php:8.4.3-alpine3.21 AS backend

ARG GIT_COMMIT
ARG GIT_TAG
ENV GIT_COMMIT=${GIT_COMMIT}
ENV GIT_TAG=${GIT_TAG}
ENV OTEL_SERVICE_VERSION=${GIT_TAG}

# Install PHP extensions by running a script based on file to cache docker layer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions
COPY docker/php-extensions.txt /etc/php-extensions.txt
COPY docker/install-wrapper.sh /usr/local/bin/install-wrapper.sh
RUN chmod +x /usr/local/bin/install-wrapper.sh
RUN /usr/local/bin/install-wrapper.sh /etc/php-extensions.txt

COPY --from=ghcr.io/roadrunner-server/roadrunner:2024.3.4 /usr/bin/rr /usr/bin/rr

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY --from=mjml /templates/out /app/app/views/email

WORKDIR /app

COPY composer.json /app
COPY composer.lock /app

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --ignore-platform-reqs --optimize-autoloader --no-dev --no-scripts

COPY . /app

EXPOSE 8080/tcp

ENTRYPOINT [ "rr", "serve", "-c", "/app/.rr.yaml" ]
