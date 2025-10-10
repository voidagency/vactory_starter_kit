.PHONY: build run

build:
	docker build \
		--build-arg DRONE_SOURCE_BRANCH=ADD_YOUR_SOURCE_BRANCH_HERE \
		--build-arg DRONE_COMMIT=ADD_YOUR_COMMIT_HASH_HERE \
		--build-arg BITBUCKET_AUTHTOKEN=ADD_YOUR_BITBUCKET_AUTHTOKEN_HERE \
		-t test-vactory:latest .

run:
	docker run --rm test-vactory:latest
