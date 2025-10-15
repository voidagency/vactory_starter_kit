.PHONY: clone

clone:
	@echo "Running setup..."
	$(MAKE) create-volume
	$(MAKE) run-git-clone
	@echo "Cloning vactory_starter_kit..."

create-volume:
	@echo "Creating volume..."
	docker volume create drone-workspace-volume
	docker volume create sonar-cache
	docker volume create composer-cache

run-git-clone:
	@echo "Running container..."
	docker run --rm \
	--volume=drone-workspace-volume:/var/www/html \
	-e BITBUCKET_AUTHTOKEN=${BITBUCKET_AUTHTOKEN} \
	-e DRONE_COMMIT=${DRONE_COMMIT} \
	--mount type=bind,source=$(CURDIR)/steps/clone/entrypoint.sh,target=/entrypoint.sh \
	--entrypoint /entrypoint.sh \
	vactory/php-ubi9:8.3.14

run-phpcs-container:
	@echo "Running phpcs container..."
	docker run --rm \
	--volume=drone-workspace-volume:/var/www/html \
	-e DRONE_TARGET_BRANCH=${DRONE_TARGET_BRANCH} \
	-e DRONE_SOURCE_BRANCH=${DRONE_SOURCE_BRANCH} \
	--mount type=bind,source=$(CURDIR)/steps/phpcs/entrypoint.sh,target=/entrypoint.sh \
	--entrypoint /entrypoint.sh \
	vactory/php-ubi9:8.3.14

run-phpunit-container:
	@echo "Running phpunit container..."
	docker network create phpunti-network

	docker run -d --name mysql-server \
	-e MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD} \
	-e MYSQL_DATABASE=${MYSQL_DATABASE} \
	-e MYSQL_USER=${MYSQL_USER} \
	-e MYSQL_PASSWORD=${MYSQL_PASSWORD} \
	--network phpunti-network \
	mysql/mysql-server:latest
	docker run --rm \
	--volume=drone-workspace-volume:/var/www/html \
	-e DRONE_COMMIT=${DRONE_COMMIT} \
	-e DRONE_TARGET_BRANCH=${DRONE_TARGET_BRANCH} \
	-e DRONE_SOURCE_BRANCH=${DRONE_SOURCE_BRANCH} \
	-e MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD} \
	-e MYSQL_DATABASE=${MYSQL_DATABASE} \
	-e MYSQL_USER=${MYSQL_USER} \
	-e MYSQL_PASSWORD=${MYSQL_PASSWORD} \
	-e MYSQL_HOST=mysql-server \
	--network phpunti-network \
	--mount type=bind,source=$(CURDIR)/steps/phpunit/entrypoint.sh,target=/entrypoint.sh \
	--entrypoint /entrypoint.sh \
	vactory/php-ubi9:8.3.14
	docker stop mysql-server
	docker rm mysql-server
	docker network rm phpunti-network

clean-phpunit-container:
	@echo "Cleaning phpunit container..."
	docker stop mysql-server
	docker rm mysql-server
	docker network rm phpunti-network

tear-down:
	@echo "Tearing down..."
	docker volume rm drone-workspace-volume

phpcs: clone run-phpcs-container


build-all:
	docker build \
		-f ./Dockerfile \
		-t all-vactory:latest .

run-all:
	docker run --rm --name=all-vactory \
	-v sonar-cache:/sonar-cache \
	-v composer-cache:/composer-cache \
	-e BITBUCKET_AUTHTOKEN=${BITBUCKET_AUTHTOKEN} \
	-e DRONE_COMMIT=${DRONE_COMMIT} \
	-e DRONE_TARGET_BRANCH=${DRONE_TARGET_BRANCH} \
	-e DRONE_SOURCE_BRANCH=${DRONE_SOURCE_BRANCH} \
	-e PHP_VERSION=${PHP_VERSION} \
	-e COMPOSER_VERSION=${COMPOSER_VERSION} \
	-e DRUSH_VERSION=${DRUSH_VERSION} \
	-e NODE_VERSION=${NODE_VERSION} \
	-e SONAR_TOKEN=${SONAR_TOKEN} \
	-e COMPOSER_CACHE_DIR=/composer-cache \
	all-vactory:latest

# build-tests:
# 	docker build \
# 		-f .ci/Dockerfile \
# 		--build-arg DRONE_SOURCE_BRANCH=$(DRONE_SOURCE_BRANCH) \
# 		--build-arg DRONE_COMMIT=$(DRONE_COMMIT) \
# 		--build-arg BITBUCKET_AUTHTOKEN=$(BITBUCKET_AUTHTOKEN) \
# 		-t test-vactory:latest .ci

# run-tests:
# 	docker run --rm test-vactory:latest

# run-tests-dev:
# 	docker run --rm -e DEV_MODE=true phpcs-vactory:latest



# docker run --entrypoint=backend.sh golang
# docker volume create my-named-volume
# docker run --volume=my-named-volume:/drone/src golang
