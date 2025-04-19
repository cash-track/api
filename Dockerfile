FROM node:20-alpine3.18 AS mjml

WORKDIR /templates

COPY ./app/views/email-templates .

RUN npm install -g mjml &&  \
    mkdir out &&  \
    ./build.sh ./out


FROM cashtrack/base-php:1.0.3 AS backend

ARG GIT_COMMIT
ARG GIT_TAG
ENV GIT_COMMIT=${GIT_COMMIT}
ENV GIT_TAG=${GIT_TAG}
ENV OTEL_SERVICE_VERSION=${GIT_TAG}

WORKDIR /app

COPY --from=mjml --chown=appuser:appgroup /templates/out /app/app/views/email

COPY --chown=appuser:appgroup composer.json /app
COPY --chown=appuser:appgroup composer.lock /app

RUN composer install --ignore-platform-reqs --optimize-autoloader --no-dev --no-scripts --no-interaction --no-progress

COPY --chown=appuser:appgroup . /app

EXPOSE 8080/tcp

ENTRYPOINT ["/usr/bin/rr", "serve", "-c", "/app/.rr.yaml"]
CMD []
