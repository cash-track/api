include .env.build
export

# Local config
CONTAINER_NAME=cashtrack_api
CONTAINER_PORT=8080

# Base deploy config
BASE_REPO=cashtrack/base-php
BASE_IMAGE_RELEASE=$(BASE_REPO):$(BASE_RELEASE_VERSION)
BASE_IMAGE_DEV=$(BASE_REPO):dev
BASE_IMAGE_LATEST=$(BASE_REPO):latest

# Deploy config
REPO=cashtrack/api
IMAGE_RELEASE=$(REPO):$(RELEASE_VERSION)
IMAGE_DEV=$(REPO):dev
IMAGE_LATEST=$(REPO):latest
WORKDIR=$(shell pwd)

.PHONY: build-base tag-base push-base build tag push start stop network phpcs psalm test-env-start test-env-stop email-build

build-base:
	docker build -f base.Dockerfile -t $(BASE_IMAGE_DEV) .

tag-base:
	docker tag $(BASE_IMAGE_DEV) $(BASE_IMAGE_RELEASE)
	docker tag $(BASE_IMAGE_DEV) $(BASE_IMAGE_LATEST)

push-base:
	docker push $(BASE_IMAGE_RELEASE)
	docker push $(BASE_IMAGE_LATEST)

build:
	docker build -t $(IMAGE_DEV) .

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
	./vendor/bin/psalm --php-version=8.4 --show-info=true --no-cache

test-env-start:
	cd ./tests && docker-compose up -d

test-env-stop:
	cd ./tests && docker-compose down

email-build:
	cd ./app/views/email-templates && ./build.sh ../email
