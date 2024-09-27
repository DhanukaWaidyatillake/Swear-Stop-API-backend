# VERSION defines the version for the docker containers.
VERSION ?= v0.0.1

# REGISTRY defines the registry where we store our images.
REGISTRY ?= danukawaidyatillake

# Commands
docker: docker-tag docker-push

# We build the images for the amd64 processor (Since amd64 is utilized in the Kubernetes nodes)
docker-tag:
	docker buildx build --platform linux/amd64 -t ${REGISTRY}/text-moderator-api-app:${VERSION} ./

docker-push:
	docker push ${REGISTRY}/text-moderator-api-app:${VERSION}
