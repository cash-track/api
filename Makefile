include .env.build
export

# Local config
CONTAINER_NAME=cashtrack_api
CONTAINER_PORT=3002

# Deploy config
REPO=cashtrack/api
IMAGE_RELEASE=$(REPO):$(RELEASE_VERSION)
IMAGE_DEV=$(REPO):dev
IMAGE_LATEST=$(REPO):latest

.PHONY: build tag push start stop

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
      --name $(CONTAINER_NAME) \
      -p $(CONTAINER_PORT):8080 \
      --env-file .env \
      -d \
      $(IMAGE_DEV)

stop:
	docker stop $(CONTAINER_NAME)
