.PHONY: build-tests run-tests

build-tests:
	docker build \
		-f .ci/Dockerfile \
		--build-arg DRONE_SOURCE_BRANCH=$(DRONE_SOURCE_BRANCH) \
		--build-arg DRONE_COMMIT=$(DRONE_COMMIT) \
		--build-arg BITBUCKET_AUTHTOKEN=$(BITBUCKET_AUTHTOKEN) \
		-t test-vactory:latest .ci

run-tests:
	docker run --rm test-vactory:latest

run-tests-dev:
	docker run --rm -e DEV_MODE=true test-vactory:latest