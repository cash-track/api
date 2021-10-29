# API

[![Check](https://github.com/cash-track/api/actions/workflows/pull.yml/badge.svg)](https://github.com/cash-track/api/actions/workflows/pull.yml) [![Release](https://github.com/cash-track/api/actions/workflows/release.yml/badge.svg)](https://github.com/cash-track/api/actions/workflows/release.yml) [![codecov](https://codecov.io/gh/cash-track/api/branch/rc-1.0.0/graph/badge.svg?token=FHDLE3MWW6)](https://codecov.io/gh/cash-track/api)

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
$ php app.php migrate:init
$ php app.php migrate
```

