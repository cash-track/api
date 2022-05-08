include .env.build
export

# Local config
CONTAINER_NAME=cashtrack_api
CONTAINER_PORT=8080

# Deploy config
REPO=cashtrack/api
IMAGE_RELEASE=$(REPO):$(RELEASE_VERSION)
IMAGE_DEV=$(REPO):dev
IMAGE_LATEST=$(REPO):latest
WORKDIR=$(shell pwd)

.PHONY: build tag push start stop network phpcs psalm test-env-start test-env-stop

build:
	docker build . -t $(IMAGE_DEV)

tag:
	docker tag $(IMAGE_DEV) $(IMAGE_RELEASE)
	docker tag $(IMAGE_DEV) $(IMAGE_LATEST)

push:
	docker push $(IMAGE_RELEASE)
	docker push $(IMAGE_LATEST)

start:
	docker run \
      --rm \
      --name $(CONTAINER_NAME) \
      -p $(CONTAINER_PORT):8080 \
      --env-file .env \
      -v $(WORKDIR):/app \
      --net cash-track-local \
      $(IMAGE_DEV) \
      -o "http.pool.debug=true" \
      -o "logs.mode=development" \
      -o "logs.encoding=console" \
      -o "logs.level=debug"

stop:
	docker stop $(CONTAINER_NAME)

network:
	docker network create --driver bridge cash-track-local || true

phpcs:
	# Arguments used
	#
	# -p - Show progress
	# -n - Does not print a warnings
	# -colors - Support console colors
	# --report=code - Add problem code piece bellow error message
	# --standard=PSR12 - Define a target standard to check (PSR12 is not accepted yet by PHP-FIG)
	./vendor/bin/phpcs -p -n --standard=PSR12 --colors --report=code ./app/src

psalm:
	./vendor/bin/psalm --php-version=8.0 --show-info=true

test-env-start:
	cd ./tests && docker-compose up -d

test-env-stop:
	cd ./tests && docker-compose down
