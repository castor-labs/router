COMPOSE_FLAGS = --project-directory .castor/docker --env-file=.castor/docker/.env
COMPOSE_CMD = docker-compose $(COMPOSE_FLAGS)

build:
	$(COMPOSE_CMD) build

up:
	$(COMPOSE_CMD) up -d --remove-orphans

deps:
	$(COMPOSE_CMD) exec main composer install

test:
	$(COMPOSE_CMD) exec main vendor/bin/phpunit --coverage-text

fmt:
	$(COMPOSE_CMD) exec main vendor/bin/php-cs-fixer fix

analysis:
	$(COMPOSE_CMD) exec main vendor/bin/psalm --stats --no-cache --show-info=true

pr: fmt analysis test
	cat .castor/msg/pr.txt

# The setup job should setup the project and leave it ready for development
setup: build up deps
	cat .castor/msg/setup.txt