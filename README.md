# API

[![quality](https://github.com/cash-track/api/actions/workflows/quality.yml/badge.svg)](https://github.com/cash-track/api/actions/workflows/quality.yml) [![security](https://github.com/cash-track/api/actions/workflows/security.yml/badge.svg)](https://github.com/cash-track/api/actions/workflows/security.yml) [![codecov](https://codecov.io/gh/cash-track/api/branch/master/graph/badge.svg?token=FHDLE3MWW6)](https://codecov.io/gh/cash-track/api)

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


## License

Copyright (c) Volodymyr Komarov. Source-available under the
[PolyForm Noncommercial License 1.0.0](LICENSE): free to use, modify and share
for noncommercial purposes. Any use that earns money needs a separate license,
see [COMMERCIAL.md](COMMERCIAL.md). Forks must not present themselves as the
official Cash Track.
