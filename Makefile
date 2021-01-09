HOST_GROUP_ID = $(shell id -g)
HOST_USER = $USER
HOST_UID = $(shell id -u)

export HOST_UID
export HOST_USER
export HOST_GROUP_ID

DOCKER_COMPOSE_DEV = docker-compose

help: ## Display available commands
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

# =====================================================================
# Install =============================================================
# =====================================================================

install: ## Install docker stack, assets and vendors
	$(DOCKER_COMPOSE_DEV) build
	$(MAKE) composer-install
	$(MAKE) assets-install
	$(MAKE) db-migrate
	$(MAKE) js-deps-install
	$(MAKE) fixtures-install

db-migrate: ## Migrate database
	$(DOCKER_COMPOSE_DEV) run --rm php bash -ci 'php bin/console doctrine:migration:migrate --no-interaction'

composer-install: ## Install composer vendor
	$(DOCKER_COMPOSE_DEV) run --rm php bash -ci 'php -d memory_limit=4G bin/composer install'

js-deps-install: ## Install javascript modules
	$(DOCKER_COMPOSE_DEV) run --rm yarn bash -ci 'yarn install'

assets-install: ## Install composer vendor and setup assets
	$(DOCKER_COMPOSE_DEV) run --rm php bash -ci 'php bin/console assets:install'

fixtures-install: ## Install fixtures
	$(DOCKER_COMPOSE_DEV) run --rm php bash -ci 'php bin/console doctrine:fixtures:load -n'

grumphp-install: ## Install GrumPhp settings
	$(DOCKER_COMPOSE_DEV) run --rm php bash -ci 'php vendor/bin/grumphp git:init'

# =====================================================================
# Development =========================================================
# =====================================================================

start: ## Start all the stack (you can access the project on localhost:8080 after that)
	@-docker network create thinkfab_stack
	$(DOCKER_COMPOSE_DEV) up -d
	$(DOCKER_COMPOSE_DEV) run --rm php bash -ci './clear-cache.sh'
	echo `cat ./docker/doc/start.txt`

stop: ## Stop all the containers that belongs to the project
	@-docker network disconnect thinkfab_stack $$(docker ps --filter "name=logitio_php_*" -q)
	$(DOCKER_COMPOSE_DEV) down

connect: ## Connect on a remote bash terminal on the php container
	$(DOCKER_COMPOSE_DEV) exec php bash

log: ## Show logs from php container
	$(DOCKER_COMPOSE_DEV) logs -f php

status: ## Check container status
	$(DOCKER_COMPOSE_DEV) ps

yarn-connect: ## Connect on a remove bash terminal on the yarn container
	$(DOCKER_COMPOSE_DEV) run yarn bash

yarn-upgrade: ## Upgrade yarn dependencies
	@read -p "Enter vendor name: (empty to update all)" package; \
	$(DOCKER_COMPOSE_DEV) run --rm yarn bash -ci 'yarn upgrade $$package'

yarn-add: ## Add yarn dependencies
	@read -p "Enter package name: " package; \
	$(DOCKER_COMPOSE_DEV) run --rm yarn bash -ci 'yarn add $$package'

build-assets: ## Build assets (minimized) for production
	$(DOCKER_COMPOSE_DEV) run --rm yarn bash -ci 'yarn encore production'

build-assets-watch: ## Build assets for dev in watch mode
	$(DOCKER_COMPOSE_DEV) run --rm yarn bash -ci 'yarn encore dev --watch'

composer-require: ## Add composer dependencies
	$(DOCKER_COMPOSE_DEV) run --rm php bash -ci "php -d memory_limit=-1 bin/composer require"

composer-update: ## Update composer dependencies
	@read -p "Enter vendor name: (empty to update all)" vendor; \
	$(DOCKER_COMPOSE_DEV) run --rm php bash -ci "php -d memory_limit=-1 bin/composer update $$vendor"

default: help