# API

Core service to handle requests from clients like web UI, mobile app, etc.

## Push to registry

```bash
$ docker build . -t cashtrack/api:latest --no-cache
$ docker push cashtrack/api:latest
```

## Install

```bash
$ cp .env.sample .env
$ composer install
$ php app.php encrypt:key -m .env
$ php app.php configure -vv
$ vendor/bin/spiral get-binary
```

