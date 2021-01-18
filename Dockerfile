FROM php:7.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
  build-essential \
  nano \
  libzip-dev \
  unzip

# Install PHP Extensions
RUN docker-php-ext-install zip mbstring pdo_mysql mysqli

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=spiralscout/roadrunner:1.9.1 /usr/bin/rr /usr/bin/rr

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json /app
COPY composer.lock /app

RUN composer install --ignore-platform-reqs --no-scripts

COPY . /app

EXPOSE 8080

ENTRYPOINT [ "rr", "serve", "-d", "-v", "-c", "/app/.rr.yaml" ]
